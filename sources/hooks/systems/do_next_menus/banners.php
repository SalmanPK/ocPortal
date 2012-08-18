<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		banners
 */


class Hook_do_next_menus_banners
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @return array			Array of links and where to show
	 */
	function run()
	{
		if (!addon_installed('banners')) return array();

		return array(
			array('cms','banners',array('cms_banners',array('type'=>'misc'),get_module_zone('cms_banners')),do_lang_tempcode('ITEMS_HERE',do_lang_tempcode('BANNERS'),make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_select_value_if_there('banners','COUNT(*)',NULL,'',true))))),('DOC_BANNERS')),
			array('usage','banners',array('admin_banners',array('type'=>'misc'),get_module_zone('admin_banners')),do_lang_tempcode('BANNER_STATISTICS'),('DOC_BANNERS')),
		);
	}

}


