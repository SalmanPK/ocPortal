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
 * An option has dissappeared somehow - find it via searching our code-base for it's install code. It doesn't get returned, just loaded up. This function will produce a fatal error if we cannot find it.
 *
 * @return boolean                      Whether to run in multi-lang mode.
 */
function _multi_lang()
{
    global $MULTI_LANG_CACHE;

    $_dir = opendir(get_file_base() . '/lang/');
    $_langs = array();
    while (false !== ($file = readdir($_dir))) {
        if (($file != fallback_lang()) && ($file[0] != '.') && ($file[0] != '_') && ($file != 'index.html') && ($file != 'langs.ini') && ($file != 'map.ini')) {
            if (is_dir(get_file_base() . '/lang/' . $file)) {
                $_langs[$file] = 'lang';
            }
        }
    }
    closedir($_dir);
    if (!in_safe_mode()) {
        $_dir = @opendir(get_custom_file_base() . '/lang_custom/');
        if ($_dir !== false) {
            while (false !== ($file = readdir($_dir))) {
                if (($file != fallback_lang()) && ($file[0] != '.') && ($file[0] != '_') && ($file != 'index.html') && ($file != 'langs.ini') && ($file != 'map.ini') && (!isset($_langs[$file]))) {
                    if (is_dir(get_custom_file_base() . '/lang_custom/' . $file)) {
                        $_langs[$file] = 'lang_custom';
                    }
                }
            }
            closedir($_dir);
        }
        if (get_custom_file_base() != get_file_base()) {
            $_dir = @opendir(get_file_base() . '/lang_custom/');
            if ($_dir !== false) {
                while (false !== ($file = readdir($_dir))) {
                    if (($file != fallback_lang()) && ($file[0] != '.') && ($file[0] != '_') && ($file != 'index.html') && ($file != 'langs.ini') && ($file != 'map.ini') && (!isset($_langs[$file]))) {
                        if (is_dir(get_file_base() . '/lang_custom/' . $file)) {
                            $_langs[$file] = 'lang_custom';
                        }
                    }
                }
                closedir($_dir);
            }
        }
    }

    foreach ($_langs as $lang => $dir) {
        if (/*optimisation*/is_file((($dir == 'lang_custom')?get_custom_file_base():get_file_base()) . '/' . $dir . '/' . $lang . '/global.ini')) {
            $MULTI_LANG_CACHE = true;
            break;
        }

        $_dir2 = @opendir((($dir == 'lang_custom')?get_custom_file_base():get_file_base()) . '/' . $dir . '/' . $lang);
        if ($_dir2 !== false) {
            while (false !== ($file2 = readdir($_dir2))) {
                if ((substr($file2,-4) == '.ini') || (substr($file2,-3) == '.po')) {
                    $MULTI_LANG_CACHE = true;
                    break;
                }
            }
        }
    }

    return $MULTI_LANG_CACHE;
}

/**
 * Get the default value of a config option.
 *
 * @param  ID_TEXT                      The name of the option
 * @return ?SHORT_TEXT                  The value (NULL: disabled)
 */
function get_default_option($name)
{
    require_code('hooks/systems/config/' . filter_naughty($name));
    $ob = object_factory('Hook_config_' . $name);

    $value = $ob->get_default();
    if (is_null($value)) {
        $value = '';
    } // Cannot save a NULL. We don't need to save as NULL anyway, options are only disabled when they wouldn't have been used anyway

    return $value;
}

/**
 * Set a configuration option with the specified values.
 *
 * @param  ID_TEXT                      The name of the value
 * @param  LONG_TEXT                    The value
 * @param  BINARY                       Whether this was a human-set value
 */
function set_option($name,$value,$will_be_formally_set = 1)
{
    global $CONFIG_OPTIONS_CACHE;

    if (!isset($CONFIG_OPTIONS_CACHE[$name])) { // Not installed with a DB setting row, so install it; even if it's just the default, we need it for performance
        require_code('hooks/systems/config/' . filter_naughty($name));
        $ob = object_factory('Hook_config_' . $name);
        $option = $ob->get_details();

        $needs_dereference = ($option['type'] == 'transtext' || $option['type'] == 'transline' || $option['type'] == 'comcodetext' || $option['type'] == 'comcodeline')?1:0;

        $map = array(
            'c_name' => $name,
            'c_set' => $will_be_formally_set,
            'c_value' => $value,
            'c_needs_dereference' => $needs_dereference,
        );
        if ($needs_dereference == 1) {
            $map = insert_lang('c_value_trans',$value,1)+$map;
        } else {
            $map['c_value_trans'] = multi_lang_content()?null:'';
        }

        $CONFIG_OPTIONS_CACHE[$name] = $map;

        if ($will_be_formally_set == 0 && $GLOBALS['IN_MINIKERNEL_VERSION']) {
            return;
        } // Don't save in the installer

        $GLOBALS['SITE_DB']->query_insert('config',$map);
    } else {
        $needs_dereference = $CONFIG_OPTIONS_CACHE[$name]['c_needs_dereference'];
    }

    if ($needs_dereference == 1) { // Translated
        $current_value = $CONFIG_OPTIONS_CACHE[$name]['c_value_trans'];
        $map = array('c_set' => $will_be_formally_set);
        if ($current_value === NULL) {
            $map += insert_lang('c_value_trans',$value,1);
        } else {
            $map += lang_remap('c_value_trans',$current_value,$value);
        }
        $GLOBALS['SITE_DB']->query_update('config',$map,array('c_name' => $name),'',1);

        $CONFIG_OPTIONS_CACHE[$name] = $map+$CONFIG_OPTIONS_CACHE[$name];
    } else { // Not translated
        $GLOBALS['SITE_DB']->query_update('config',array('c_value' => $value,'c_set' => $will_be_formally_set),array('c_name' => $name),'',1);

        $CONFIG_OPTIONS_CACHE[$name]['c_value'] = $value;
    }

    // For use by get_option during same script execution
    $CONFIG_OPTIONS_CACHE[$name]['c_value_translated'] = $value;
    $CONFIG_OPTIONS_CACHE[$name]['c_set'] = $will_be_formally_set;

    // Log it
    if ((function_exists('log_it')) && ($will_be_formally_set == 1)) {
        require_lang('config');
        log_it('CONFIGURATION',$name,$value);
    }

    // Update persistent cache
    if (function_exists('persistent_cache_delete')) {
        persistent_cache_delete('OPTIONS');
    }
}

/**
 * Update a reference stored in a config option.
 *
 * @param  SHORT_TEXT                   The old value
 * @param  SHORT_TEXT                   The name value
 * @param  ID_TEXT                      The type
 */
function config_update_value_ref($old_setting,$setting,$type)
{
    $hooks = find_all_hooks('systems','config');
    $all_options = array();
    foreach (array_keys($hooks) as $hook) {
        require_code('hooks/systems/config/' . filter_naughty($hook));
        $ob = object_factory('Hook_config_' . $hook);
        $option = $ob->get_details();
        if (($option['type'] == $type) && (get_option($hook) == $old_setting)) {
            $GLOBALS['FORUM_DB']->query_update('config',array('c_value' => $setting),array('c_name' => $hook),'',1);
        }
    }
}

/**
 * Update a reference stored in a config option.
 *
 * @param  ID_TEXT                      The config option name
 * @return ?URLPATH                     URL to set the config option (NULL: no such option exists)
 */
function config_option_url($name)
{
    $value = get_option($name,true);
    if (is_null($value)) {
        return NULL;
    }

    require_code('hooks/systems/config/' . filter_naughty($name));
    $ob = object_factory('Hook_config_' . $name);
    $option = $ob->get_details();

    $_config_url = build_url(array('page' => 'admin_config','type' => 'category','id' => $option['category']),get_module_zone('admin_config'));
    $config_url = $_config_url->evaluate();
    $config_url .= '#group_' . $option['group'];

    return $config_url;
}

/**
 * Deletes a specified config option permanently from the database.
 *
 * @param  ID_TEXT                      The codename of the config option
 */
function delete_config_option($name)
{
    $rows = $GLOBALS['SITE_DB']->query_select('config',array('*'),array('c_name' => $name),'',1);
    if (array_key_exists(0,$rows)) {
        $myrow = $rows[0];
        if (($myrow['c_needs_dereference'] == 1) && (is_numeric($myrow['c_value']))) {
            delete_lang($myrow['c_value_trans']);
        }
        $GLOBALS['SITE_DB']->query_delete('config',array('c_name' => $name),'',1);
        /*global $CONFIG_OPTIONS_CACHE;  Don't do this, it will cause problems in some parts of the code
        unset($CONFIG_OPTIONS_CACHE[$name]);*/
    }
    if (function_exists('persistent_cache_delete')) {
        persistent_cache_delete('OPTIONS');
    }
}

/**
 * Rename a config option.
 *
 * @param  ID_TEXT                      The old name
 * @param  ID_TEXT                      The new name
 */
function rename_config_option($old,$new)
{
    $GLOBALS['SITE_DB']->query_update('config',array('c_name' => $new),array('c_name' => $old),'',1);
    if (function_exists('persistent_cache_delete')) {
        persistent_cache_delete('OPTIONS');
    }
}
