<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_menus
 */

class Hook_resource_meta_aware_menu
{

	/**
	 * Standard modular info function for content hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @param  ?ID_TEXT	The zone to link through to (NULL: autodetect).
	 * @return ?array		Map of award content-type info (NULL: disabled).
	 */
	function info($zone=NULL)
	{
		return array(
			'supports_custom_fields'=>false,

			'content_type_label'=>'MENUS',

			'connection'=>$GLOBALS['SITE_DB'],
			'table'=>'menu_items',
			'id_field'=>'i_menu',
			'id_field_numeric'=>false,
			'parent_category_field'=>NULL,
			'parent_category_meta_aware_type'=>NULL,
			'is_category'=>true,
			'is_entry'=>false,
			'category_field'=>NULL, // For category permissions
			'category_type'=>NULL, // For category permissions
			'category_is_string'=>true,

			'title_field'=>'i_menu',
			'title_field_dereference'=>false,

			'view_pagelink_pattern'=>NULL,
			'edit_pagelink_pattern'=>'_SEARCH:admin_menus:_edit:_WILD',
			'view_category_pagelink_pattern'=>NULL,
			'add_url'=>(has_actual_page_access('admin_menus'))?(get_module_zone('admin_menus').':admin_menus:edit'):NULL,
			'archive_url'=>NULL,

			'support_url_monikers'=>false,

			'views_field'=>NULL,
			'submitter_field'=>NULL,
			'add_time_field'=>NULL,
			'edit_time_field'=>NULL,
			'date_field'=>NULL,
			'validated_field'=>NULL,

			'seo_type_code'=>NULL,

			'feedback_type_code'=>NULL,

			'permissions_type_code'=>NULL, // NULL if has no permissions

			'search_hook'=>NULL,

			'addon_name'=>'core_menus',

			'cms_page'=>'admin_menus',
			'module'=>NULL,

			'occle_filesystem_hook'=>'menus',
			'occle_filesystem__is_folder'=>true,

			'rss_hook'=>NULL,

			'actionlog_regexp'=>'\w+_MENU',
		);
	}

}
