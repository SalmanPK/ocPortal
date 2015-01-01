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
 * Hook class.
 */
class Hook_page_groupings_core
{
    /**
     * Run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
     *
     * @param  ?MEMBER                  $member_id Member ID to run as (null: current member)
     * @param  boolean                  $extensive_docs Whether to use extensive documentation tooltips, rather than short summaries
     * @return array                    List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
     */
    public function run($member_id = null, $extensive_docs = false)
    {
        require_code('site');

        if (is_null($member_id)) {
            $member_id = get_member();
        }

        return array(
            array('', 'menu/adminzone/start', array('start', array(), 'adminzone'), do_lang_tempcode('menus:DASHBOARD'), $extensive_docs ? 'menus:DOC_DASHBOARD' : 'menus:MM_TOOLTIP_DASHBOARD'),
            array('', 'menu/adminzone/audit', array('admin', array('type' => 'audit'), get_module_zone('admin')), do_lang_tempcode('menus:AUDIT'), $extensive_docs ? 'menus:DOC_AUDIT' : 'menus:MM_TOOLTIP_AUDIT'),
            array('', 'menu/adminzone/security', array('admin', array('type' => 'security'), get_module_zone('admin')), do_lang_tempcode('SECURITY'), $extensive_docs ? 'menus:DOC_SECURITY' : 'menus:MM_TOOLTIP_SECURITY'),
            array('', 'menu/adminzone/setup', array('admin', array('type' => 'setup'), get_module_zone('admin')), do_lang_tempcode('menus:SETUP'), $extensive_docs ? 'menus:DOC_SETUP' : 'menus:MM_TOOLTIP_SETUP'),
            array('', 'menu/adminzone/structure', array('admin', array('type' => 'structure'), get_module_zone('admin')), do_lang_tempcode('menus:STRUCTURE'), $extensive_docs ? 'menus:DOC_STRUCTURE' : 'menus:MM_TOOLTIP_STRUCTURE'),
            array('', 'menu/adminzone/style', array('admin', array('type' => 'style'), get_module_zone('admin')), do_lang_tempcode('menus:STYLE'), $extensive_docs ? 'menus:DOC_STYLE' : 'menus:MM_TOOLTIP_STYLE'),
            array('', 'menu/adminzone/tools', array('admin', array('type' => 'tools'), get_module_zone('admin')), do_lang_tempcode('menus:TOOLS'), $extensive_docs ? 'menus:DOC_TOOLS' : 'menus:MM_TOOLTIP_TOOLS'),
            (_request_page('website', 'adminzone') === null) ? array('', 'menu/adminzone/help', get_brand_base_url() . '/docs' . strval(ocp_version()) . '/', do_lang_tempcode('menus:DOCS')) : null,
            (_request_page('website', 'adminzone') === null) ? null : array('', 'menu/adminzone/help', array('website', array(), 'adminzone'), do_lang_tempcode('menus:DOCS')),
            array('', 'menu/cms/cms', array('cms', array('type' => 'cms'), get_module_zone('cms')), do_lang_tempcode('CONTENT'), $extensive_docs ? 'menus:CONTENT' : 'menus:MM_TOOLTIP_CMS'),

            ((has_some_edit_comcode_page_permission(COMCODE_EDIT_OWN | COMCODE_EDIT_ANY)) || (get_comcode_page_editability_per_zone() != array())) ? array('cms', 'menu/cms/comcode_page_edit', array('cms_comcode_pages', array('type' => 'browse'), get_module_zone('cms_comcode_pages')), do_lang_tempcode('ITEMS_HERE', do_lang_tempcode('menus:_COMCODE_PAGES'), make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(DISTINCT the_zone,the_page) FROM ' . get_table_prefix() . 'comcode_pages WHERE ' . db_string_not_equal_to('the_zone', '!')))))), 'zones:DOC_COMCODE_PAGE_EDIT') : null,

            array('structure', 'menu/adminzone/structure/zones/zones', array('admin_zones', array('type' => 'browse'), get_module_zone('admin_zones')), do_lang_tempcode('ZONES'), 'zones:DOC_ZONES'),
            array('structure', 'menu/adminzone/structure/zones/zone_editor', array('admin_zones', array('type' => 'editor'), get_module_zone('admin_zones')), do_lang_tempcode('zones:ZONE_EDITOR'), 'zones:DOC_ZONE_EDITOR'),
            array('structure', 'menu/adminzone/structure/menus', array('admin_menus', array('type' => 'browse'), get_module_zone('admin_menus')), do_lang_tempcode('menus:MENU_MANAGEMENT'), 'menus:DOC_MENUS'),
            addon_installed('page_management') ? array('structure', 'menu/adminzone/structure/sitemap/sitemap_editor', array('admin_sitemap', array('type' => has_js() ? 'sitemap' : 'browse'), get_module_zone('admin_sitemap')), do_lang_tempcode(has_js() ? 'zones:SITEMAP_EDITOR' : 'zones:SITEMAP_TOOLS'), 'zones:DOC_SITEMAP_EDITOR') : null,
            addon_installed('redirects_editor') ? array('structure', 'menu/adminzone/structure/redirects', array('admin_redirects', array('type' => 'browse'), get_module_zone('admin_redirects')), do_lang_tempcode('redirects:REDIRECTS'), 'redirects:DOC_REDIRECTS') : null,
            addon_installed('breadcrumbs') ? array('structure', 'menu/adminzone/structure/breadcrumbs', array('admin_config', array('type' => 'xml_breadcrumbs'), get_module_zone('admin_config')), do_lang_tempcode('config:BREADCRUMB_OVERRIDES'), 'config:DOC_BREADCRUMB_OVERRIDES') : null,
            array('structure', 'menu/adminzone/structure/addons', array('admin_addons', array('type' => 'browse'), get_module_zone('admin_addons')), do_lang_tempcode('addons:ADDONS'), 'addons:DOC_ADDONS'),

            (get_forum_type() != 'ocf' || !addon_installed('ocf_cpfs')) ? null : array('audit', 'menu/adminzone/tools/users/custom_profile_fields', array('admin_ocf_customprofilefields', array('type' => 'stats'), get_module_zone('admin_ocf_customprofilefields')), do_lang_tempcode('ocf:CUSTOM_PROFILE_FIELD_STATS'), 'ocf:DOC_CUSTOM_PROFILE_FIELDS_STATS'),
            addon_installed('errorlog') ? array('audit', 'menu/adminzone/audit/errorlog', array('admin_errorlog', array(), get_module_zone('admin_errorlog')), do_lang_tempcode('errorlog:ERROR_LOG'), 'errorlog:DOC_ERROR_LOG') : null,
            addon_installed('actionlog') ? array('audit', 'menu/adminzone/audit/actionlog', array('admin_actionlog', array('type' => 'browse'), get_module_zone('admin_actionlog')), do_lang_tempcode('actionlog:VIEW_ACTIONLOGS'), 'actionlog:DOC_ACTIONLOG') : null,
            addon_installed('securitylogging') ? array('audit', 'menu/adminzone/audit/security_log', array('admin_security', array('type' => 'browse'), get_module_zone('admin_security')), do_lang_tempcode('security:SECURITY_LOG'), 'security:DOC_SECURITY_LOG') : null,
            array('audit', 'menu/adminzone/audit/email_log', array('admin_email_log', array('type' => 'browse'), get_module_zone('admin_email_log')), do_lang_tempcode('emaillog:EMAIL_LOG'), 'emaillog:DOC_EMAIL_LOG'),
            (get_forum_type() != 'ocf') ? null : array('audit', 'buttons/history', array('admin_ocf_history', array('type' => 'browse'), get_module_zone('admin_ocf_history')), do_lang_tempcode('ocf:POST_HISTORY'), 'ocf:DOC_POST_HISTORY'),
            (!addon_installed('content_reviews')) ? null : array('audit', 'menu/adminzone/audit/content_reviews', array('admin_content_reviews', array('type' => 'browse'), get_module_zone('admin_content_reviews')), do_lang_tempcode('content_reviews:CONTENT_REVIEWS'), 'content_reviews:DOC_CONTENT_REVIEWS'),

            array('style', 'menu/adminzone/style/themes/themes', array('admin_themes', array('type' => 'browse'), get_module_zone('admin_themes')), do_lang_tempcode('THEMES'), 'DOC_THEMES'),
            (get_forum_type() != 'ocf') ? null : array('style', 'menu/adminzone/style/emoticons', array('admin_ocf_emoticons', array('type' => 'browse'), get_module_zone('admin_ocf_emoticons')), do_lang_tempcode('ITEMS_HERE', do_lang_tempcode('EMOTICONS'), make_string_tempcode(escape_html(integer_format($GLOBALS['FORUM_DB']->query_select_value_if_there('f_emoticons', 'COUNT(*)', null, '', true))))), 'ocf:DOC_EMOTICONS'),

            array('setup', 'menu/adminzone/setup/config/config', array('admin_config', array('type' => 'browse'), get_module_zone('admin_config')), do_lang_tempcode('CONFIGURATION'), 'DOC_CONFIGURATION'),
            addon_installed('awards') ? array('setup', 'menu/adminzone/setup/awards', array('admin_awards', array('type' => 'browse'), get_module_zone('admin_awards')), do_lang_tempcode('awards:AWARDS'), 'awards:DOC_AWARDS') : null,
            addon_installed('awards') ? array('site_meta', 'menu/adminzone/setup/awards', array('awards', array('type' => 'browse'), get_module_zone('awards')), do_lang_tempcode('awards:AWARDS')) : null,
            (get_forum_type() == 'ocf' || !addon_installed('welcome_emails')) ?/*Is on members menu*/
                null : array('setup', 'menu/adminzone/setup/welcome_emails', array('admin_ocf_welcome_emails', array('type' => 'browse'), get_module_zone('admin_ocf_welcome_emails')), do_lang_tempcode('ocf_welcome_emails:WELCOME_EMAILS'), 'ocf_welcome_emails:DOC_WELCOME_EMAILS'),
            ((get_forum_type() == 'ocf')/*Is on members menu*/ || (addon_installed('securitylogging'))) ? null : array('tools', 'menu/adminzone/tools/users/investigate_user', array('admin_lookup', array(), get_module_zone('admin_lookup')), do_lang_tempcode('lookup:INVESTIGATE_USER'), 'lookup:DOC_INVESTIGATE_USER'),
            addon_installed('xml_fields') ? array('setup', 'menu/adminzone/setup/xml_fields', array('admin_config', array('type' => 'xml_fields'), get_module_zone('admin_config')), do_lang_tempcode('config:FIELD_FILTERS'), 'config:DOC_FIELD_FILTERS') : null,

            (get_forum_type() != 'ocf') ? null : array('tools', 'menu/adminzone/tools/users/member_add', array('admin_ocf_members', array(), get_module_zone('admin_ocf_members')), do_lang_tempcode('MEMBERS'), 'ocf:DOC_MEMBERS'),
            //((get_forum_type()!='ocf')||(!has_privilege($member_id,'control_usergroups')))?null:array('tools','menu/social/groups',array('groups',array('type'=>'browse'),get_module_zone('groups'),do_lang_tempcode('SWITCH_ZONE_WARNING')),do_lang_tempcode('SECONDARY_GROUP_MEMBERSHIP'),'DOC_SECONDARY_GROUP_MEMBERSHIP'),
            array('tools', 'menu/adminzone/tools/cleanup', array('admin_cleanup', array('type' => 'browse'), get_module_zone('admin_cleanup')), do_lang_tempcode('cleanup:CLEANUP_TOOLS'), 'cleanup:DOC_CLEANUP_TOOLS'),
            (is_null(get_value('brand_base_url'))) ? array('tools', 'menu/adminzone/tools/upgrade', array('admin_config', array('type' => 'upgrader'), get_module_zone('admin_config')), do_lang_tempcode('upgrade:FU_UPGRADER_TITLE'), 'upgrade:FU_UPGRADER_INTRO') : null,
            (addon_installed('syndication')) ? array('tools', 'links/rss', array('admin_config', array('type' => 'backend'), get_module_zone('admin_config')), do_lang_tempcode('FEEDS'), 'rss:OPML_INDEX_DESCRIPTION') : null,
            (addon_installed('code_editor')) ? array('tools', 'menu/adminzone/tools/code_editor', array('admin_config', array('type' => 'code_editor'), get_module_zone('admin_config')), do_lang_tempcode('CODE_EDITOR'), 'DOC_CODE_EDITOR') : null,

            array('security', 'menu/adminzone/security/permissions/permission_tree_editor', array('admin_permissions', array('type' => 'browse'), get_module_zone('admin_permissions')), do_lang_tempcode('permissions:PERMISSIONS_TREE'), 'permissions:DOC_PERMISSIONS_TREE'),
            addon_installed('match_key_permissions') ? array('security', 'menu/adminzone/security/permissions/match_keys', array('admin_permissions', array('type' => 'match_keys'), get_module_zone('admin_permissions')), do_lang_tempcode('permissions:PAGE_MATCH_KEY_ACCESS'), 'permissions:DOC_PAGE_MATCH_KEY_ACCESS') : null,
            //array('security','menu/adminzone/security/permissions/permission_tree_editor',array('admin_permissions',array('type'=>'page'),get_module_zone('admin_permissions')),do_lang_tempcode('permissions:PAGE_ACCESS'),'permissions:DOC_PAGE_PERMISSIONS'),  // Disabled as not needed - but tree permission editor will redirect to it if no javascript available
            addon_installed('securitylogging') ? array('security', 'menu/adminzone/security/ip_ban', array('admin_ip_ban', array('type' => 'browse'), get_module_zone('admin_ip_ban')), do_lang_tempcode('submitban:BANNED_ADDRESSES'), 'submitban:DOC_IP_BAN') : null,
            array('security', 'menu/adminzone/security/permissions/privileges', array('admin_permissions', array('type' => 'privileges'), get_module_zone('admin_permissions')), do_lang_tempcode('permissions:GLOBAL_PRIVILEGES'), 'permissions:DOC_PRIVILEGES'),
            (get_forum_type() != 'ocf') ? null : array('security', 'menu/social/groups', array('admin_ocf_groups', array('type' => 'browse'), get_module_zone('admin_ocf_groups')), do_lang_tempcode('ocf:USERGROUPS'), 'ocf:DOC_GROUPS'),
            (get_forum_type() != 'ocf') ? null : array('security', 'menu/adminzone/security/usergroups_temp', array('admin_group_member_timeouts', array('type' => 'browse'), get_module_zone('admin_group_member_timeouts')), do_lang_tempcode('group_member_timeouts:GROUP_MEMBER_TIMEOUTS'), 'group_member_timeouts:DOC_MANAGE_GROUP_MEMBER_TIMEOUTS'),
            (get_forum_type() == 'ocf') ? null : array('security', 'menu/social/groups', array('admin_permissions', array('type' => 'absorb'), get_module_zone('admin_security')), do_lang_tempcode('permissions:ABSORB_PERMISSIONS'), 'permissions:DOC_ABSORB_PERMISSIONS'),

            //(get_comcode_zone('start',false)===NULL)?null:array('','menu/start',array('start',array(),get_comcode_zone('start')),do_lang_tempcode('HOME')),  Attached to zone, so this is not needed
            array('', 'menu/pages', array('admin', array('type' => 'pages'), 'adminzone'), do_lang_tempcode('PAGES')),
            array('', 'menu/rich_content', array('admin', array('type' => 'rich_content'), 'adminzone'), do_lang_tempcode('menus:RICH_CONTENT')),
            array('', 'menu/site_meta', array('admin', array('type' => 'site_meta'), 'adminzone'), do_lang_tempcode('menus:SITE_META')),
            array('', 'menu/social', array('admin', array('type' => 'social'), 'adminzone'), do_lang_tempcode('SECTION_SOCIAL')),

            (get_comcode_zone('about', false) === null || get_comcode_zone('about', false) == 'collaboration') ? null : array('pages', 'menu/pages/about_us', array('about', array(), get_comcode_zone('about')), do_lang_tempcode('menus:ABOUT')),

            //(get_comcode_zone('keymap',false)===NULL)?null:array('site_meta',/*'menu/pages/keymap'*/NULL,array('keymap',array(),get_comcode_zone('keymap')),do_lang_tempcode('KEYBOARD_MAP')),   Shows as child of help page
            (get_comcode_zone('privacy', false) === null || get_option('bottom_show_privacy_link') == '1') ? null : array('site_meta', 'menu/pages/privacy_policy', array('privacy', array(), get_comcode_zone('privacy')), do_lang_tempcode('PRIVACY')),
            (get_comcode_zone('rules', false) === null || get_option('bottom_show_rules_link') == '1') ? null : array('site_meta', 'menu/pages/rules', array('rules', array(), get_comcode_zone('rules')), do_lang_tempcode('RULES')),
            (get_comcode_zone('feedback', false) === null || get_option('bottom_show_feedback_link') == '1') ? null : array('site_meta', 'menu/site_meta/contact_us', array('feedback', array(), get_comcode_zone('feedback')), do_lang_tempcode('FEEDBACK')),
            //(get_comcode_zone('sitemap',false)===NULL || get_option('bottom_show_sitemap_button')=='1')?null:array('site_meta','tool_buttons/sitemap',array('sitemap',array(),get_comcode_zone('sitemap')),do_lang_tempcode('SITEMAP')),   Redundant, menu itself is a sitemap
            // userguide_comcode is child of help_page
            (get_forum_type() == 'none' || !is_guest($member_id)) ? null : array('site_meta', 'menu/site_meta/user_actions/login', array('login', array(), ''), do_lang_tempcode('_LOGIN')),
            //(get_forum_type()=='none' || is_guest($member_id))?null:array('site_meta','menu/site_meta/user_actions/logout',array('login',array(),''),do_lang_tempcode('LOGOUT')), Don't show an immediate action, don't want accidental preloading
            (get_forum_type() != 'ocf') ? null : array('site_meta', 'menu/site_meta/user_actions/join', array('join', array(), get_module_zone('join')), do_lang_tempcode('_JOIN')),
            (get_forum_type() != 'ocf') ? null : array('site_meta', 'menu/site_meta/user_actions/lost_password', array('lost_password', array(), get_module_zone('lost_password')), do_lang_tempcode('ocf:LOST_PASSWORD')),

            (get_forum_type() != 'ocf') ? null : array('social', 'menu/social/groups', array('groups', array(), get_module_zone('groups')), do_lang_tempcode('ocf:USERGROUPS')),
            (get_forum_type() != 'ocf') ? null : array('social', 'menu/social/members', array('members', array(), get_module_zone('members')), do_lang_tempcode('ocf:MEMBER_DIRECTORY')),
            (get_forum_type() != 'ocf') ? null : array('social', 'menu/social/users_online', array('users_online', array(), get_module_zone('users_online')), do_lang_tempcode('USERS_ONLINE')),

            (get_forum_type() == 'ocf' || get_forum_type() == 'none') ? null : array('social', 'menu/social/forum/forums', get_forum_base_url(), do_lang_tempcode('SECTION_FORUMS')),
            (get_forum_type() == 'ocf' || get_forum_type() == 'none') ? null : array('social', 'menu/social/members', $GLOBALS['FORUM_DRIVER']->member_profile_url($member_id, true), do_lang_tempcode('MY_PROFILE')),
            (get_forum_type() == 'ocf' || get_forum_type() == 'none') ? null : array('site_meta', 'menu/site_meta/user_actions/join', $GLOBALS['FORUM_DRIVER']->join_url(), do_lang_tempcode('_JOIN')),
            (get_forum_type() == 'ocf' || get_forum_type() == 'none') ? null : array('social', 'menu/social/users_online', $GLOBALS['FORUM_DRIVER']->users_online_url(), do_lang_tempcode('USERS_ONLINE')),
        );
    }
}
