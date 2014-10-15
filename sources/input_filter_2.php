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
 * Detect if the POST request was shortened due to a limitation.
 * If we're staff, try and fix it. If we're not staff, warn about it (as fixing it would subvert the reason for the restriction).
 */
function rescue_shortened_post_request()
{
    $setting_value = mixed();
    $setting_name = mixed();
    foreach (array('max_input_vars','suhosin.post.max_vars','suhosin.request.max_vars') as $setting) {
        if ((is_numeric(ini_get($setting))) && (intval(ini_get($setting))>10)) {
            $this_setting_value = intval(ini_get($setting_value));
            if ($this_setting_value<$setting_value) {
                $setting_value = $this_setting_value;
                $setting_name = $setting;
            }
        }
    }

    if (($setting_value !== NULL) && ($setting_value>1/*sanity check*/)) {
        if ((count($_POST) >= $setting_value-5) || (array_count_recursive($_POST) >= $setting_value-5)) {
            if (has_zone_access(get_member(),'adminzone')) {
                $post = parse_raw_http_request();
                if ($post !== NULL) {
                    $_POST = $post;
                    return;
                }
            }

            warn_exit(do_lang_tempcode('_SUHOSIN_MAX_VARS_TOO_LOW',escape_html($setting_name)));
        }
    }
}

/**
 * Count how many elements in an array, recursively.
 *
 * @param   array    The array
 * @return  integer  The count
 */
function array_count_recursive($arr)
{
    $count = 0;
    foreach (array_values($arr) as $val) {
        $count++;
        if (is_array($val)) {
            $count += array_count_recursive($val);
        }
    }

    return $count;
}

/**
 * Parse raw HTTP request data.
 * Based on https://gist.github.com/chlab/4283560
 *
 * @return  ?array   Associative array of request data (NULL: could not rescue)
 */
function parse_raw_http_request()
{
    if (!isset($_SERVER['CONTENT_TYPE'])) {
        return NULL;
    }

    $post_data = array();

    // Read incoming data
    $input = file_get_contents('php://input');

    // Grab multipart boundary from content type header
    $matches = array();
    preg_match('#boundary=(.*)$#',$_SERVER['CONTENT_TYPE'],$matches);

    // Content type is probably regular form-encoded
    if (count($matches) == 0) {
        $pairs = explode('&',$input);
        foreach ($pairs as $pair) {
            $exploded = explode('=',$pair,2);
            if (count($exploded) == 2) {
                $key = urldecode($exploded[0]);
                $val = urldecode($exploded[1]);
                if (get_magic_quotes_gpc()) {
                    $val = addslashes($val);
                }

                if (substr($key,-2) == '[]') {
                    $key = substr($key,0,strlen($key)-2);
                    if (!isset($post_data[$key])) {
                        $post_data[$key] = array();
                    }
                    $post_data[$key][] = $val;
                } else {
                    $post_data[$key] = $val;
                }
            }
        }

        return $post_data;
    }

    // Multipart encoded (unfortunately for modern PHP versions, this code can't run as php://input is not populated for multipart)...

    $boundary = $matches[1];

    // Split content by boundary and get rid of last -- element
    $blocks = preg_split("#-+$boundary#",$input);
    array_pop($blocks);

    // Loop data blocks
    foreach ($blocks as $block) {
        // Parse blocks (except for uploaded files)
        if (strpos($block,'application/octet-stream') === false) {
            // Match "name" and optional value in between newline sequences
            if (preg_match('#name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?[\r\n]$#s',$block,$matches) != 0) {
                $key = $matches[1];
                $val = $matches[2];
                if (get_magic_quotes_gpc()) {
                    $val = addslashes($val);
                }

                if (substr($key,-2) == '[]') {
                    $key = substr($key,0,strlen($key)-2);
                    if (!isset($post_data[$key])) {
                        $post_data[$key] = array();
                    }
                    $post_data[$key][] = $val;
                } else {
                    $post_data[$key] = $val;
                }
            }
        }
    }

    return $post_data;
}
