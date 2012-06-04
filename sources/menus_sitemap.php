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
 * @package		core_menus
 */

/**
 * Recursive helper function for build_sitetree_menu.
 *
 * @param  ?integer		Parent's ID (NULL: make root level)
 * @param  array			Children to recurse with
 * @param  array			Tuple of information about our sitetree segment
 * @param  string			Page link of root branch (we monitor against this, making sure we use a NULL parent if we're a child of this)
 * @param  string			Page the links are scoped to
 * @return array			Faked database rows
 */
function _build_sitetree_menu_deep($this_id,$children,$pagelinks,$page_link,$page)
{
	$items=array();
	foreach ($children as $pagelink)
	{
		$new_id=mt_rand(0,100000);

		$actual_page_link=str_replace('!',is_string($pagelink['id'])?$pagelink['id']:strval($pagelink['id']),$pagelinks[2]);
		$actual_page_link=str_replace('_SELF:_SELF',get_module_zone($page).':'.$page,$actual_page_link); // Support for lazy notation

		$items[]=array('id'=>$new_id,'i_parent'=>$this_id,'cap'=>$pagelink['title'],'i_url'=>$actual_page_link,'i_check_permissions'=>0,'i_expanded'=>0,'i_new_window'=>0,'i_page_only'=>'');

		$items=array_merge($items,_build_sitetree_menu_deep(($actual_page_link!=$page_link)?$new_id:NULL,$pagelink['children'],$pagelinks,$page_link,$page));
	}

	return $items;
}

/**
 * Build menu based off of site tree branch.
 *
 * @param  array			Where to get the site-tree under
 * @return array			Faked database rows
 */
function build_sitetree_menu($specifier)
{
	if (count($specifier)<2) return array();

	$zone=$specifier[0];
	$page=$specifier[1];

	require_code('zones2');

	$page_link=implode(':',$specifier);

	$items=array();
	$parents=array();

	$_pagelinks=extract_module_functions_page($zone,$page,array('get_page_links'),array(NULL,false,$page_link));
	if (!is_null($_pagelinks[0])) // If it's a CMS-supporting module (e.g. downloads)
	{
		$pagelinks=is_array($_pagelinks[0])?call_user_func_array($_pagelinks[0][0],$_pagelinks[0][1]):eval($_pagelinks[0]);
		if (!is_null($pagelinks[1])) // If it's not disabled
		{
			foreach ($pagelinks[0] as $pagelink)
			{
				$this_id=mt_rand(0,100000);
				$parent=NULL;

				$keys=array_keys($pagelink);
				if (is_string($keys[0])) // Map style
				{
					$module_the_name=array_key_exists(3,$pagelinks)?$pagelinks[3]:NULL;
					$category_name=is_string($pagelink['id'])?$pagelink['id']:strval($pagelink['id']);
					$actual_page_link=str_replace('!',$category_name,$pagelinks[2]);
					$actual_page_link=str_replace('_SELF:_SELF',get_module_zone($page).':'.$page,$actual_page_link); // Support for lazy notation
					$title=$pagelink['title'];
					if (array_key_exists('children',$pagelink))
					{
						$items=array_merge($items,_build_sitetree_menu_deep(($actual_page_link!=$page_link)?$this_id:NULL,$pagelink['children'],$pagelinks,$page_link,$page));
					}
					if ((array_key_exists('second_cat',$pagelink)) && ($pagelink['second_cat']!=''))
					{
						if (!array_key_exists($pagelink['second_cat'],$parents))
						{
							$new_parent_id=mt_rand(0,100000);
							$items[]=array('id'=>$new_parent_id,'i_parent'=>NULL,'cap'=>$pagelink['second_cat'],'i_url'=>'','i_check_permissions'=>0,'i_expanded'=>0,'i_new_window'=>0,'i_page_only'=>'');
							$parents[$pagelink['second_cat']]=$new_parent_id;
						}
						$parent=$parents[$pagelink['second_cat']];
					}
				} else // Explicit list style. Cannot display the tree structures for these yet.
				{
					$module_the_name=$pagelink[1];
					$actual_page_link=$pagelink[0];
					$actual_page_link=str_replace('_SELF:_SELF',get_module_zone($page).':'.$page,$actual_page_link); // Support for lazy notation
					$title=$pagelink[3];
				}

				if ($actual_page_link!=$page_link) // We only want children
					$items[]=array('id'=>$this_id,'i_parent'=>$parent,'cap'=>$title,'i_url'=>$actual_page_link,'i_check_permissions'=>0,'i_expanded'=>0,'i_new_window'=>0,'i_page_only'=>'');
			}
		}
	}

	return $items;
}

