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
 * @package    core
 */

/**
 * Find the user-ID of the current shared-site install from the accessing URL. This should only ever be called when it is known a shared-site is in operation
 *
 * @return ?ID_TEXT                     The shared-site install (null: not on one)
 */
function current_share_user()
{
    global $CURRENT_SHARE_USER;
    if (!is_null($CURRENT_SHARE_USER)) {
        return $CURRENT_SHARE_USER;
    }

    global $SITE_INFO;
    $custom_share_domain = $SITE_INFO['custom_share_domain'];
    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_ENV['HTTP_HOST']) ? $_ENV['HTTP_HOST'] : $custom_share_domain);
    $domain = preg_replace('#:\d+#', '', $domain);
    if ($domain == $custom_share_domain) { // Get from the access path
        $path = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_ENV['SCRIPT_NAME']) ? $_ENV['SCRIPT_NAME'] : '');
        if (substr($path, 0, strlen($SITE_INFO['custom_share_path']) + 1) == '/' . $SITE_INFO['custom_share_path']) {
            $path = substr($path, strlen($SITE_INFO['custom_share_path']) + 1);
        }
        $slash = strpos($path, '/');
        if ($slash !== false) {
            $path = substr($path, 0, $slash);
            if (array_key_exists('custom_user_' . $path, $SITE_INFO)) {
                $CURRENT_SHARE_USER = $path;
                return $CURRENT_SHARE_USER;
            }
        }
    } else {
        // Get from map
        if (array_key_exists('custom_domain_' . $domain, $SITE_INFO)) {
            $CURRENT_SHARE_USER = $SITE_INFO['custom_domain_' . $domain];
            return $CURRENT_SHARE_USER;
        }
        // Get from subdomain
        $domain = substr($domain, 0, -strlen($custom_share_domain) - 1);
        if (array_key_exists('custom_user_' . $domain, $SITE_INFO)) {
            $CURRENT_SHARE_USER = $domain;
            return $domain;
        }
    }

    if ($CURRENT_SHARE_USER == '') {
        $CURRENT_SHARE_USER = null;
        return null;
    }

    header('Content-type: text/plain; charset=' . get_charset());
    if (array_key_exists('no_website_redirect', $SITE_INFO)) {
        header('Location: ' . $SITE_INFO['no_website_redirect']);
    }
    exit('No such web-site on the server, ' . $domain);
}
