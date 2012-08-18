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
 * @package		core_feedback_features
 */


class Hook_do_next_menus_trackbacks
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @return array			Array of links and where to show
	 */
	function run()
	{
		if ((get_option('is_on_trackbacks')=='0') || ($GLOBALS['SITE_DB']->query_select_value_if_there('trackbacks','COUNT(*)',NULL,'',true)==0)) return array();
		return array(
			array('usage','trackbacks',array('admin_trackbacks',array('type'=>'misc'),get_module_zone('admin_trackbacks')),do_lang_tempcode('MANAGE_TRACKBACKS'),('DOC_TRACKBACKS')),
		);
	}

}


