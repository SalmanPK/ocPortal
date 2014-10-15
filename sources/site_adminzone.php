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
 * Special code to render Admin Zone Comcode pages with special significances.
 *
 * @param  ID_TEXT                      The page being loaded
 */
function adminzone_special_cases($codename)
{
    /*
    The current design does not require these, but this code may be useful in the future.
    If we put it back, we should do it with hooks, for proper modularity.

    if (($codename=='start') && (get_page_name()=='start') && (get_option('show_docs')!=='0'))
    {
        require_lang('menus');
        set_helper_panel_text(comcode_lang_string('menus:DOC_ADMIN_ZONE'));
        set_helper_panel_tutorial('tut_adminzone');
    }
    elseif (($codename=='netlink') && (get_page_name()=='netlink'))
    {
        set_helper_panel_text(comcode_lang_string('menus:DOC_NETLINK'));
        set_helper_panel_tutorial('tut_msn');
    }
*/

    if ($codename == 'start') {
        // Various checks
        $hooks = find_all_hooks('systems','checks');
        $found_issues = false;
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/checks/' . filter_naughty($hook));
            $ob = object_factory('Hook_check_' . $hook);
            $warning = $ob->run();
            foreach ($warning as $_warning) {
                attach_message($_warning,'warn');
            }
        }
    }
}

/**
 * Extend breadcrumbs for the Admin Zone (called by breadcrumbs_get_default_stub).
 *
 * @param  tempcode                     Reference to the breadcrumbs stub we're assembling
 */
function adminzone_extend_breadcrumbs(&$stub)
{
    global $BREADCRUMB_SET_PARENTS;

    if ((count($BREADCRUMB_SET_PARENTS)>0) && (!is_object($BREADCRUMB_SET_PARENTS[0][0]))) { // Ideally
        // Works by finding where our oldest ancestor connects on to the do-next menus, and carries from there
        list($zone,$attributes,) = page_link_decode($BREADCRUMB_SET_PARENTS[0][0]);
        $type = array_key_exists('type',$attributes)?$attributes['type']:'misc';
        $page = $attributes['page'];
        if ($page == '_SELF') {
            $page = get_page_name();
        }
        if ($zone == '_SEARCH') {
            $zone = get_module_zone($page);
        }
        if ($zone == '_SELF') {
            $zone = get_zone_name();
        }
    } else {
        // Works by finding where we connect on to the do-next menus, and carries from there
        $type = get_param('type','misc');
        $page = get_page_name();
        $zone = get_zone_name();
    }

    if (($page != 'admin') && ($page != 'cms')) {
        // Loop over menus, hunting for connection
        $hooks = find_all_hooks('systems','page_groupings');
        $_hooks = array();
        $page_looking = $page;
        $page_looking = preg_replace('#^(cms|admin)\_#','',$page_looking);
        if (array_key_exists($page_looking,$hooks)) {
            $_hooks[$page_looking] = $hooks[$page_looking];
            unset($hooks[$page_looking]);
            $hooks = array_merge($_hooks,$hooks);
        }
        foreach ($hooks as $hook => $sources_dir) {
            $run_function = extract_module_functions(get_file_base() . '/' . $sources_dir . '/hooks/systems/page_groupings/' . $hook . '.php',array('run'));
            if ($run_function[0] !== NULL) {
                $info = is_array($run_function[0])?call_user_func_array($run_function[0][0],$run_function[0][1]):eval($run_function[0]);

                foreach ($info as $i) {
                    if ($i === NULL) {
                        continue;
                    }

                    if ((is_array($i[2])) && ($page == $i[2][0]) && (((!isset($i[2][1]['type'])) && ($type == 'misc')) || ((isset($i[2][1]['type'])) && (($type == $i[2][1]['type']) || ($i[2][1]['type'] == 'misc')))) && ($zone == $i[2][2])) {
                        if ($i[0] == 'cms') {
                            $url = build_url(array('page' => 'cms','type' => ($i[0] == 'cms')?null:$i[0]),'cms');
                        } else {
                            $url = build_url(array('page' => 'admin','type' => $i[0]),'adminzone');
                        }

                        $title = do_lang_tempcode(strtoupper($i[0])); // The lang string version of the page grouping we found our current module was in

                        $stub->attach(hyperlink($url,$title,false,false,do_lang_tempcode('GO_BACKWARDS_TO',@html_entity_decode(strip_tags($title->evaluate()),ENT_QUOTES,get_charset()))));

                        return;
                    }
                }
            }
        }
    }
}
