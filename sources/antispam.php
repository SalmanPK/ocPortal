<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/**
 * Standard code module initialisation function.
 */
function init__antispam()
{
    define('ANTISPAM_RESPONSE_SKIP',-2);
    define('ANTISPAM_RESPONSE_ERROR',-1);
    define('ANTISPAM_RESPONSE_UNLISTED',0);
    define('ANTISPAM_RESPONSE_STALE',1);
    define('ANTISPAM_RESPONSE_ACTIVE',2);
    define('ANTISPAM_RESPONSE_ACTIVE_UNKNOWN_STALE',3);
}

/**
 * Should be called when an action happens that results in content submission. Does a spammer check.
 *
 * @param ?string    Check this particular username that has just been supplied (NULL: none)
 * @param ?string    Check this particular email address that has just been supplied (NULL: none)
 */
function inject_action_spamcheck($username = null,$email = null)
{
    // Check RBL's/stopforumspam
    $spam_check_level = get_option('spam_check_level');
    if (($spam_check_level == 'EVERYTHING') || ($spam_check_level == 'ACTIONS') || ($spam_check_level == 'GUESTACTIONS') && (is_guest())) {
        check_rbls();
        check_stopforumspam($username,$email);
    }
}

/**
 * Check RBLs to see if we need to block this user.
 *
 * @param boolean    Whether this is a page level check (i.e. we won't consider blocks or approval, just ban setting)
 * @param ?IP        IP address (NULL: current user's)
 */
function check_rbls($page_level = false,$user_ip = null)
{
    if (is_null($user_ip)) {
        $user_ip = get_ip_address();
    }

    // Check ocPortal bans / caching
    require_code('global4');
    $is_already_ip_banned = ip_banned($user_ip,true,true);
    if ($is_already_ip_banned === true) {
        critical_error('BANNED');
    }
    if ($is_already_ip_banned === false) {
        return;
    } // Cached that we're not banned

    // Check exclusions
    $exclusions = explode(',',get_option('spam_check_exclusions'));
    foreach ($exclusions as $e) {
        if (trim($e) == $user_ip) {
            return;
        }
    }

    // Handle the return data for the different RBLs
    $is_blocked = mixed();
    $blocked_by = mixed();
    $confidence_level = mixed();
    $rbl_list = explode(',',get_option('spam_block_lists'));
    foreach ($rbl_list as $rbl) {
        list($_is_potential_blocked,$_confidence_level) = check_rbl($rbl,$user_ip,!is_null($confidence_level),$page_level);
        if ($_is_potential_blocked == ANTISPAM_RESPONSE_ACTIVE || $_is_potential_blocked == ANTISPAM_RESPONSE_ACTIVE_UNKNOWN_STALE) { // If it is a potential block
            // If this is a stronger block than we've seen so far
            if ((!is_null($_confidence_level)) || (is_null($confidence_level)) || ($confidence_level<$_confidence_level)) {
                $confidence_level = $_confidence_level;
                $blocked_by = preg_replace('#(^|\.)\*(\.|$)#','',$rbl);
                $is_blocked = true;
            }
        }
    }

    // Now deal with it
    if ($is_blocked) { // If there's a block
        if ($confidence_level === NULL) {
            $confidence_level = floatval(get_option('implied_spammer_confidence'))/100.0;
        }
        handle_perceived_spammer_by_confidence($user_ip,$confidence_level,$blocked_by,$page_level);
    } else {
        require_code('failure');
        add_ip_ban($user_ip,'',time()+60*intval(get_option('spam_cache_time')),false); // Mark a negative ban (i.e. cache)
    }
}

/**
 * Do an RBL check on an IP address.
 *
 * @param  ID_TEXT                      The RBL domain name/IP (HTTP:BL has a special syntax)
 * @param  IP                           The IP address to lookup
 * @param  boolean                      If true, then no RBL check will happen if the RBL has no scoring, because it can't provide a superior result to what is already known (performance)
 * @param boolean    Whether this is a page level check (i.e. we won't consider blocks or approval, just ban setting)
 * @return array                        Pair: Listed for potential blocking as a ANTISPAM_RESPONSE_* constant, confidence level if attainable (0.0 to 1.0) (else NULL)
 */
function check_rbl($rbl,$user_ip,$we_have_a_result_already = false,$page_level = false)
{
    // Blocking based on opm.tornevall.org settings (used by default because stopforumspam syndicates to this and ask us to check this first, for performance)
    // http://dnsbl.tornevall.org/?do=usage
    if (strpos($rbl,'tornevall.org') !== false) {
        $block = array(
            'tornevall_abuse' => true,            // TornevallRBL: Block on 'abuse'
            'tornevall_anonymous' => true,        // TornevallRBL: Block on anonymous access (anonymizers, TOR, etc)
            'tornevall_blitzed' => false,        // TornevallRBL: Block if host are found in the Blitzed RBL (R.I.P)
            'tornevall_checked' => false,        // TornevallRBL: Block anything that has been checked
            'tornevall_elite' => true,            // TornevallRBL: Block elite proxies (proxies with high anonymity)
            'tornevall_error' => false,            // TornevallRBL: Block proxies that has been tested but failed
            'tornevall_timeout' => false,        // TornevallRBL: Block proxies that has been tested but timed out
            'tornevall_working' => true,            // TornevallRBL: Block proxies that has been tested and works
        );
        $rtornevall = array(
            'tornevall_checked' => 1,
            'tornevall_working' => 2,
            'tornevall_blitzed' => 4,
            'tornevall_timeout' => 8,
            'tornevall_error' => 16,
            'tornevall_elite' => 32,
            'tornevall_abuse' => 64,
            'tornevall_anonymous' => 128
        );

        if ($we_have_a_result_already) {
            return array(ANTISPAM_RESPONSE_SKIP,null);
        } // We know better than this RBL can tell us, so stick with what we know
        $rbl_response = rbl_resolve($user_ip,$rbl,$page_level);
        if (is_null($rbl_response)) {
            return array(ANTISPAM_RESPONSE_ERROR,null);
        } // Error

        foreach ($rtornevall as $rbl_t => $rbl_tc) {
            if ((($rbl_response[3] & $rbl_tc) != 0) && ($block[$rbl_t])) {
                return array(ANTISPAM_RESPONSE_ACTIVE_UNKNOWN_STALE,null);
            }
        }
        return array(ANTISPAM_RESPONSE_UNLISTED,null); // Not listed / Not listed with a threat status
    }

    // Blocking based on njabl.org settings (not used by default)
    // http://njabl.org/use.html
    if (strpos($rbl,'njabl.org') !== false) {
        $block = array(
            'njabl_dialup' => false,                // Njabl: Block dialups/dynamic ip-ranges (Be careful!)
            'njabl_formmail' => true,                // Njabl: Block systems with insecure scripts that make them into open relays
            'njabl_multi' => false,                // Njabl: Block multi-stage open relays (Don't know what this is? Leave it alone)
            'njabl_openproxy' => true,            // Njabl: Block open proxy servers
            'njabl_passive' => false,                // Njabl: Block passively detected 'bad hosts' (Don't know what this is? Leave it alone)
            'njabl_relay' => false,                // Njabl: Block open relays (as in e-mail-open-relays - be careful)
            'njabl_spam' => false                    // Njabl: lock spam sources (Again, as in e-mail
        );
        $rnjabl = array(
            'njabl_relay' => 2,
            'njabl_dialup' => 3,
            'njabl_spam' => 4,
            'njabl_multi' => 5,
            'njabl_passive' => 6,
            'njabl_formmail' => 8,
            'njabl_openproxy' => 9
        );

        if ($we_have_a_result_already) {
            return array(ANTISPAM_RESPONSE_SKIP,null);
        } // We know better than this RBL can tell us, so stick with what we know
        $rbl_response = rbl_resolve($user_ip,$rbl,$page_level);
        if (is_null($rbl_response)) {
            return array(ANTISPAM_RESPONSE_ERROR,null);
        } // Error

        foreach ($rnjabl as $njcheck => $value) {
            if (($rbl_response[3] == $value) && ($block[$njcheck])) {
                return array(ANTISPAM_RESPONSE_ACTIVE_UNKNOWN_STALE,null);
            }
        }
        return array(ANTISPAM_RESPONSE_UNLISTED,null); // Not listed / Not listed with a threat status
    }

    // Blocking based on efnet.org settings (not used by default)
    // http://efnetrbl.org/
    if (strpos($rbl,'efnet.org') !== false) {
        $block = array(
            'efnet_openproxy' => true,            // EFNet: Block open proxies registered at rbl.efnet.org
            'efnet_spamtrap50' => false,            // EFNet: Block trojan spreading client (IRC-based)
            'efnet_spamtrap666' => false,        // EFNet: Block known trojan infected/spreading client (IRC-based)
            'efnet_tor' => true,                    // EFNet: Block TOR Proxies
            'efnet_drones' => false,                // EFNet: Drones/Flooding (IRC-based)
        );
        $refnet = array(
            'efnet_openproxy' => 1,
            'efnet_spamtrap666' => 2,
            'efnet_spamtrap50' => 3,
            'efnet_tor' => 4,
            'efnet_drones' => 5
        );

        if ($we_have_a_result_already) {
            return array(ANTISPAM_RESPONSE_SKIP,null);
        } // We know better than this RBL can tell us, so stick with what we know
        $rbl_response = rbl_resolve($user_ip,$rbl,$page_level);
        if (is_null($rbl_response)) {
            return array(ANTISPAM_RESPONSE_ERROR,null);
        } // Error

        foreach ($refnet as $efcheck => $value) {
            if (($rbl_response[3] == $value) && ($block[$njcheck])) {
                return array(ANTISPAM_RESPONSE_ACTIVE_UNKNOWN_STALE,null);
            }
        }
        return array(ANTISPAM_RESPONSE_UNLISTED,null); // Not listed / Not listed with a threat status
    }

    // Blocking based on HTTP:BL settings (not used by default, because it requires getting a key)
    // http://www.projecthoneypot.org/httpbl_api.php
    if (strpos($rbl,'dnsbl.httpbl.org') !== false) {
        if (strpos($rbl,'*') === false) { // Fix a misconfiguration based on the admin copy and pasting the given HTTP:BL setup example
            $rbl = str_replace('7.1.1.127','*',$rbl);
        }
        $rbl_response = rbl_resolve($user_ip,$rbl,$page_level);
        if (is_null($rbl_response)) {
            return array(ANTISPAM_RESPONSE_ERROR,null);
        } // Error

        $_confidence_level = floatval($rbl_response[2])/255.0;
        $threat_type = intval($rbl_response[3]);
        if (($threat_type & 1) || ($threat_type & 2) || ($threat_type & 4)) {
            if ($_confidence_level != 0.0) {
                $spam_stale_threshold = intval(get_option('spam_stale_threshold'));

                if (intval($rbl_response[1])>$spam_stale_threshold) {
                    return array(ANTISPAM_RESPONSE_STALE,null);
                } // We know this IP is stale now so don't check other RBLs as no others support stale checks

                $confidence_level = $_confidence_level*4.0; // Actually, this is a threat level, not a confidence level. We have a fudge factor to try and normalise it, seeing that Google was actually reported with a threat level.
                return array(ANTISPAM_RESPONSE_ACTIVE,$confidence_level);
            }
        }
        return array(ANTISPAM_RESPONSE_UNLISTED,null); // Not listed / Not listed with a threat status
    }

    // Unknown RBL, basic support only
    $rbl_response = rbl_resolve($user_ip,$rbl,$page_level);
    if ($rbl_response[3] != 0) {
        if ($we_have_a_result_already) {
            return array(ANTISPAM_RESPONSE_SKIP,null);
        } // We know better than this RBL can tell us, so stick with what we know
        $rbl_response = rbl_resolve($user_ip,$rbl,$page_level);
        if (is_null($rbl_response)) {
            return array(ANTISPAM_RESPONSE_ERROR,null);
        } // Error

        return array(ANTISPAM_RESPONSE_ACTIVE_UNKNOWN_STALE,null);
    }
    return array(ANTISPAM_RESPONSE_UNLISTED,null); // Not listed / Not listed with a threat status
}

/**
 * Do an RBL lookup (low level, uninterpreted).
 *
 * @param  IP                           The IP address to lookup
 * @param  ID_TEXT                      The RBL domain
 * @param  boolean                      Whether this is a page level check (i.e. we won't consider blocks or approval, just ban setting)
 * @return ?array                       Return result (NULL: error)
 */
function rbl_resolve($ip,$rbl_domain,$page_level)
{
    if (strpos($ip,'.') !== false) { // ipv4
        $arpa = implode('.',array_reverse(explode('.',$ip)));
    } else { // ipv6
        if (strpos($rbl_domain,'httpbl.org') !== false) {
            return NULL;
        } // Not supported

        $_ip = explode(':',$ip);
        $normalised_ip = '';
        $normalised_ip .= str_pad('',(4*(8-count($_ip))),'0000',STR_PAD_LEFT); // Fill out trimmed 0's on left
        foreach ($_ip as $seg) {// Copy rest in
            $normalised_ip .= str_pad($seg,4,'0',STR_PAD_LEFT);
        } // Pad out each component in full, building up $normalised_ip
        $arpa = implode('.',array_reverse(preg_split('//',$normalised_ip,null,PREG_SPLIT_NO_EMPTY)));
    }

    $lookup = str_replace('*',$arpa,$rbl_domain) . '.';

    $_result = gethostbyname($lookup);
    $result = explode('.',$_result);

    if (implode('.',$result) == $lookup) { // This is how gethostbyname indicates an error happened; however it likely actually means no block happened (as the RBL returned no data on the IP)
        return NULL;
    }

    if ($result[0] != '127') { // This is how the RBL indicates an error happened
        if (!$page_level) {
            require_code('failure');
            $error = do_lang('_ERROR_CHECKING_FOR_SPAMMERS',$rbl_domain,$_result,$ip);
            relay_error_notification($error,false,'error_occurred');
        }
        return NULL;
    }

    // Some kind of response
    return $result;
}

/**
 * Deal with a perceived spammer.
 *
 * @param IP         IP address
 * @param float      Confidence level (0.0 to 1.0)
 * @param ID_TEXT    Identifier for whatever did the blocking
 * @param boolean    Whether this is a page level check (i.e. we won't consider blocks or approval, just ban setting)
 */
function handle_perceived_spammer_by_confidence($user_ip,$confidence_level,$blocked_by,$page_level)
{
    // Ban
    $spam_ban_threshold = intval(get_option('spam_ban_threshold'));
    if (intval($confidence_level*100.0) >= $spam_ban_threshold) {
        require_code('failure');
        $ban_happened = add_ip_ban($user_ip,do_lang('IP_BAN_LOG_AUTOBAN_ANTISPAM',$blocked_by),time()+60*intval(get_option('spam_cache_time')));

        if ($ban_happened) {
            require_code('notifications');
            $subject = do_lang('NOTIFICATION_SPAM_CHECK_BLOCK_SUBJECT_BAN',$user_ip,$blocked_by,null,get_site_default_lang());
            $message = do_lang('NOTIFICATION_SPAM_CHECK_BLOCK_BODY_BAN',$user_ip,$blocked_by,null,get_site_default_lang());
            dispatch_notification('spam_check_block',null,$subject,$message,null,A_FROM_SYSTEM_PRIVILEGED);
        }

        warn_exit(do_lang_tempcode('STOPPED_BY_ANTISPAM',escape_html($user_ip),escape_html($blocked_by)));
    }

    // Block
    if (!$page_level) {
        $spam_block_threshold = intval(get_option('spam_block_threshold'));
        if (intval($confidence_level*100.0) >= $spam_block_threshold) {
            require_code('notifications');
            $subject = do_lang('NOTIFICATION_SPAM_CHECK_BLOCK_SUBJECT_BLOCK',$user_ip,$blocked_by,null,get_site_default_lang());
            $message = do_lang('NOTIFICATION_SPAM_CHECK_BLOCK_BODY_BLOCK',$user_ip,$blocked_by,null,get_site_default_lang());
            dispatch_notification('spam_check_block',null,$subject,$message,null,A_FROM_SYSTEM_PRIVILEGED);

            warn_exit(do_lang_tempcode('STOPPED_BY_ANTISPAM',escape_html($user_ip),escape_html($blocked_by)));
        }
    }

    // Require approval
    $spam_approval_threshold = intval(get_option('spam_approval_threshold'));
    if (intval($confidence_level*100.0) >= $spam_approval_threshold) {
        global $SPAM_REMOVE_VALIDATION;
        $SPAM_REMOVE_VALIDATION = true;

        require_code('notifications');
        $subject = do_lang('NOTIFICATION_SPAM_CHECK_BLOCK_SUBJECT_APPROVE',$user_ip,$blocked_by,null,get_site_default_lang());
        $message = do_lang('NOTIFICATION_SPAM_CHECK_BLOCK_BODY_APPROVE',$user_ip,$blocked_by,null,get_site_default_lang());
        dispatch_notification('spam_check_block',null,$subject,$message,null,A_FROM_SYSTEM_PRIVILEGED);
    }
}

/**
 * Check the stopforumspam service to see if we need to block this user.
 *
 * @param ?string    Check this particular username that has just been supplied (NULL: none)
 * @param ?string    Check this particular email address that has just been supplied (NULL: none)
 */
function check_stopforumspam($username = null,$email = null)
{
    if (get_option('spam_block_lists') == '') {
        return;
    }

    // Check exclusions
    $user_ip = get_ip_address();
    $exclusions = explode(',',get_option('spam_check_exclusions'));
    foreach ($exclusions as $e) {
        if (trim($e) == $user_ip) {
            return;
        }
    }

    // Are we really going to check that username?
    if (get_option('spam_check_usernames') == '0') {
        $username = null;
    }

    list($is_potential_blocked,$confidence_level) = _check_stopforumspam($user_ip,$username,$email);

    if (($confidence_level !== NULL) && ($is_potential_blocked == ANTISPAM_RESPONSE_ACTIVE)) {
        handle_perceived_spammer_by_confidence($user_ip,$confidence_level,'stopforumspam.com',false);
    }
}

/**
 * Check the stopforumspam service to see if we need to block this user (lower level, doesn't handle result).
 *
 * @param  string                       Check this IP address
 * @param  ?string                      Check this particular username that has just been supplied (NULL: none)
 * @param  ?string                      Check this particular email address that has just been supplied (NULL: none)
 * @return array                        Pair: Listed for potential blocking as a ANTISPAM_RESPONSE_* constant, confidence level if attainable (0.0 to 1.0) (else NULL)
 */
function _check_stopforumspam($user_ip,$username = null,$email = null)
{
    // http://www.stopforumspam.com/usage

    $confidence_level = mixed();
    $status = ANTISPAM_RESPONSE_UNLISTED;

    // Do the query with every detail we have
    require_code('files');
    require_code('character_sets');
    $key = get_option('stopforumspam_api_key');
    $url = 'http://www.stopforumspam.com/api?f=serial&unix&confidence&ip=' . urlencode($user_ip);
    if (!is_null($username)) {
        $url .= '&username=' . urlencode(convert_to_internal_encoding($username,get_charset(),'utf-8'));
    }
    if (!is_null($email)) {
        $url .= '&email=' . urlencode(convert_to_internal_encoding($email,get_charset(),'utf-8'));
    }
    if ($key != '') {
        $url .= '&api_key=' . urlencode($key);
    } // Key not needed for read requests, but give it as a courtesy
    $_result = http_download_file($url,null,false);

    secure_serialized_data($_result);

    $result = @unserialize($_result);
    if ($result !== false) {
        if ($result['success']) {
            foreach (array('username','email','ip') as $criterion) {
                if (array_key_exists($criterion,$result)) {
                    $c = $result[$criterion];
                    if ($c['appears'] == 1) {
                        $_confidence_level = $c['confidence']/100.0;

                        $spam_stale_threshold = intval(get_option('spam_stale_threshold'));
                        $days_ago = floatval(time()-intval($c['lastseen']))/(24.0*60.0*60.0);
                        if ($days_ago <= floatval($spam_stale_threshold)) {
                            $status = ANTISPAM_RESPONSE_ACTIVE;

                            if (($confidence_level === NULL) || ($_confidence_level>$confidence_level)) {
                                $confidence_level = $_confidence_level;
                            }
                        } else {
                            if ($status != ANTISPAM_RESPONSE_ACTIVE) { // If not found an active one yet
                                $status = ANTISPAM_RESPONSE_STALE;

                                if (($confidence_level === NULL) || ($_confidence_level>$confidence_level)) {
                                    $confidence_level = $_confidence_level;
                                }
                            }
                        }

                        // NB: frequency figure is ignored, not used in our algorithm
                    }
                }
            }
        } else {
            require_code('failure');
            $error = do_lang('_ERROR_CHECKING_FOR_SPAMMERS','stopforumspam.com',$result['error'],$user_ip);
            relay_error_notification($error,false,'error_occurred');
            return array(ANTISPAM_RESPONSE_ERROR,$confidence_level);
        }
    } else {
        require_code('failure');
        $error = do_lang('ERROR_CHECKING_FOR_SPAMMERS','stopforumspam.com',$user_ip);
        relay_error_notification($error,false,'error_occurred');
        return array(ANTISPAM_RESPONSE_ERROR,$confidence_level);
    }

    return array($status,$confidence_level);
}
