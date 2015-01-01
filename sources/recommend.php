<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    recommend
 */

/**
 * Sends out a recommendation e-mail.
 *
 * @param  string                       $name Recommenders name
 * @param  mixed                        $email_address Their e-mail address (string or array of alternates)
 * @param  string                       $message The recommendation message
 * @param  boolean                      $is_invite Whether this is an invitation
 * @param  ?string                      $recommender_email Email address of the recommender (null: current user's)
 * @param  ?string                      $subject The subject (null: default)
 * @param  ?array                       $names List of names (null: use email addresses as names)
 */
function send_recommendation_email($name, $email_address, $message, $is_invite = false, $recommender_email = null, $subject = null, $names = null)
{
    if (!is_array($email_address)) {
        $email_address = array($email_address);
    }
    if (is_null($recommender_email)) {
        $recommender_email = $GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member());
    }
    if (is_null($subject)) {
        $subject = do_lang('RECOMMEND_MEMBER_SUBJECT', get_site_name());
    }

    require_code('mail');
    if ($message == '') {
        $message = '(' . do_lang('NONE') . ')';
    }

    mail_wrap(do_lang('RECOMMEND_MEMBER_SUBJECT', get_site_name()), $message, $email_address, is_null($names) ? $email_address : $names, $recommender_email, $name);
}

/**
 * Get number of invites available for member.
 *
 * @param  MEMBER                       $member_id Member to look for
 * @return integer                      Number of invites
 */
function get_num_invites($member_id)
{
    if ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) {
        return 1; // Admin can always have another invite
    }

    $used = $GLOBALS['FORUM_DB']->query_select_value('f_invites', 'COUNT(*)', array('i_inviter' => $member_id));
    $per_day = floatval(get_option('invites_per_day'));
    return intval($per_day * floor((time() - $GLOBALS['FORUM_DRIVER']->get_member_join_timestamp($member_id)) / (60 * 60 * 24)) - $used);
}

/**
 * Whether the current member may use invites (doesn't check if they have any left though!)
 *
 * @return boolean                      Whether they may
 */
function may_use_invites()
{
    if ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) {
        return true;
    }
    return (get_option('is_on_invites') == '1');
}
