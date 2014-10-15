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
 * @package    ecommerce
 */

class Hook_resource_meta_aware_usergroup_subscription
{
    /**
     * Get content type details. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
     *
     * @param  ?ID_TEXT                 The zone to link through to (NULL: autodetect).
     * @return ?array                   Map of award content-type info (NULL: disabled).
     */
    public function info($zone = null)
    {
        return array(
            'supports_custom_fields' => false,

            'content_type_label' => 'USERGROUP_SUBSCRIPTIONS',

            'connection' => (get_forum_type() == 'ocf')?$GLOBALS['FORUM_DB']:$GLOBALS['SITE_DB'],
            'table' => 'f_usergroup_subs',
            'id_field' => 'id',
            'id_field_numeric' => true,
            'parent_category_field' => NULL,
            'parent_category_meta_aware_type' => NULL,
            'is_category' => false,
            'is_entry' => true,
            'category_field' => NULL, // For category permissions
            'category_type' => NULL, // For category permissions
            'parent_spec__table_name' => NULL,
            'parent_spec__parent_name' => NULL,
            'parent_spec__field_name' => NULL,
            'category_is_string' => true,

            'title_field' => 's_title',
            'title_field_dereference' => true,

            'view_page_link_pattern' => NULL,
            'edit_page_link_pattern' => '_SEARCH:admin_ecommerce:_ed:_WILD',
            'view_category_page_link_pattern' => NULL,
            'add_url' => (function_exists('get_member') && has_actual_page_access(get_member(),'admin_ecommerce'))?(get_module_zone('admin_ecommerce') . ':admin_ecommerce:ad'):null,
            'archive_url' => NULL,

            'support_url_monikers' => false,

            'views_field' => NULL,
            'submitter_field' => NULL,
            'add_time_field' => NULL,
            'edit_time_field' => NULL,
            'date_field' => NULL,
            'validated_field' => NULL,

            'seo_type_code' => NULL,

            'feedback_type_code' => NULL,

            'permissions_type_code' => NULL, // NULL if has no permissions

            'search_hook' => NULL,

            'addon_name' => 'ecommerce',

            'cms_page' => 'admin_ecommerce',
            'module' => NULL,

            'occle_filesystem_hook' => 'usergroup_subscriptions',
            'occle_filesystem__is_folder' => false,

            'rss_hook' => NULL,

            'actionlog_regexp' => '\w+_USERGROUP_SUBSCRIPTION',
        );
    }
}
