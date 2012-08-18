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
 * @package		catalogues
 */


class Hook_do_next_menus_catalogues
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @param  boolean		Whether to look deep into the database (or whatever else might be time-intensive) for links
	 * @return array			Array of links and where to show
	 */
	function run($exhaustive=false)
	{
		if (!addon_installed('catalogues')) return array();

		$ret=array();
		if (has_privilege(get_member(),'submit_cat_highrange_content','cms_catalogues'))
			$ret[]=array('cms','catalogues',array('cms_catalogues',array('type'=>'misc'),get_module_zone('cms_catalogues')),do_lang_tempcode('ITEMS_HERE',do_lang_tempcode('CATALOGUES'),make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_select_value_if_there('catalogues','COUNT(*)',NULL,'',true))))),('DOC_CATALOGUES'));
		if ($exhaustive)
		{
			$catalogues=$GLOBALS['SITE_DB']->query_select('catalogues',array('c_name','c_title','c_description','c_ecommerce'),NULL,'',10,NULL,true);
			if (!is_null($catalogues))
			{
				$ret2=array();
				foreach ($catalogues as $row)
				{
					if (substr($row['c_name'],0,1)=='_') continue;

					if (($row['c_ecommerce']==0) || (addon_installed('shopping')))
					{
						if (has_submit_permission('mid',get_member(),get_ip_address(),'cms_catalogues',array('catalogues_catalogue',$row['c_name'])))
							$ret2[]=array('cms','of_catalogues',array('cms_catalogues',array('type'=>'misc','catalogue_name'=>$row['c_name']),get_module_zone('cms_catalogues')),do_lang_tempcode('ITEMS_HERE',get_translated_text($row['c_title']),escape_html(integer_format($GLOBALS['SITE_DB']->query_select_value_if_there('catalogue_entries','COUNT(*)',array('c_name'=>$row['c_name']),'',true)))),get_translated_text($row['c_description']));
					}
				}
				if (count($ret2)<10)
				{
					$ret=array_merge($ret,$ret2);
				}
			}
		}
		return $ret;
	}

}


