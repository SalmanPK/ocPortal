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
 * @package		galleries
 */

class Hook_choose_image
{

	/**
	 * Standard modular run function for ajax-tree hooks. Generates XML for a tree list, which is interpreted by Javascript and expanded on-demand (via new calls).
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root)
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @return string			XML in the special category,entry format
	 */
	function run($id,$options,$default=NULL)
	{
		require_code('galleries');

		$only_owned=array_key_exists('only_owned',$options)?(is_null($options['only_owned'])?NULL:intval($options['only_owned'])):NULL;
		$editable_filter=array_key_exists('editable_filter',$options)?($options['editable_filter']):false;
		$tree=get_gallery_content_tree('images',$only_owned,$id,NULL,NULL,is_null($id)?0:1,false,$editable_filter);
		if (!has_actual_page_access(NULL,'galleries')) $tree=array();

		$out='';
		foreach ($tree as $t)
		{
			$_id=$t['id'];
			if ($id===$_id) // Possible when we look under as a root
			{
				foreach ($t['entries'] as $eid=>$etitle)
				{
					if (is_object($etitle)) $etitle=@html_entity_decode(strip_tags($etitle->evaluate()),ENT_QUOTES,get_charset());
					$out.='<entry id="'.xmlentities(strval($eid)).'" title="'.xmlentities($etitle).'" selectable="true"></entry>';
				}
				continue;
			}
			$title=$t['title'];
			$has_children=($t['child_count']!=0) || ($t['child_entry_count']!=0);

			$out.='<category id="'.xmlentities($_id).'" title="'.xmlentities($title).'" has_children="'.($has_children?'true':'false').'" selectable="false"></category>';
		}

		// Mark parent cats for pre-expansion
		if ((!is_null($default)) && ($default!=''))
		{
			$cat=$GLOBALS['SITE_DB']->query_select_value_if_there('images','cat',array('id'=>intval($default)));
			while ((!is_null($cat)) && ($cat!=''))
			{
				$out.='<expand>'.$cat.'</expand>';
				$cat=$GLOBALS['SITE_DB']->query_select_value_if_there('galleries','parent_id',array('name'=>$cat));
			}
		}

		return '<result>'.$out.'</result>';
	}

	/**
	 * Standard modular simple function for ajax-tree hooks. Returns a normal <select> style <option>-list, for fallback purposes
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root) - not always supported
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @return tempcode		The nice list
	 */
	function simple($id,$options,$it=NULL)
	{
		unset($id);

		require_code('galleries');

		$only_owned=array_key_exists('only_owned',$options)?(is_null($options['only_owned'])?NULL:intval($options['only_owned'])):NULL;
		$editable_filter=array_key_exists('editable_filter',$options)?($options['editable_filter']):false;
		return nice_get_gallery_content_tree('images',$it,$only_owned,false,$editable_filter);
	}

}


