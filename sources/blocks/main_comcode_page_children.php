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
 * @package		core
 */

class Block_main_comcode_page_children
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		$info['parameters']=array('param','zone');
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='array(has_privilege(get_member(),\'see_unvalidated\'),((array_key_exists(\'param\',$map)) && ($map[\'param\']!=\'\'))?$map[\'param\']:get_page_name(),array_key_exists(\'zone\',$map)?$map[\'zone\']:post_param(\'zone\',get_comcode_zone(((array_key_exists(\'param\',$map)) && ($map[\'param\']!=\'\'))?$map[\'param\']:get_page_name(),false)))';
		$info['ttl']=60*24*7;
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{	
		$page=((array_key_exists('param',$map)) && ($map['param']!=''))?$map['param']:get_page_name();
		$zone=array_key_exists('zone',$map)?$map['zone']:post_param('zone',get_comcode_zone($page,false));
		if ($zone=='_SEARCH') $zone=NULL;
		$qmap=array('p_parent_page'=>$page);
		if (!is_null($zone)) $qmap['the_zone']=$zone;
		if (!has_privilege(get_member(),'see_unvalidated')) $qmap['p_validated']=1;
		$children=$GLOBALS['SITE_DB']->query_select('comcode_pages',array('the_page','the_zone'),$qmap);
		foreach ($children as $i=>$child)
		{
			$_title=$GLOBALS['SITE_DB']->query_select_value_if_there('cached_comcode_pages','cc_page_title',array('the_page'=>$child['the_page'],'the_zone'=>$child['the_zone']));
			if (!is_null($_title))
			{
				$title=get_translated_text($_title,NULL,NULL,true);
				if (is_null($title)) $title='';
			} else
			{
				$title='';

				if (get_option('is_on_comcode_page_cache')=='1') // Try and force a parse of the page
				{
					request_page($child['the_page'],false,$child['the_zone'],NULL,true);
					$_title=$GLOBALS['SITE_DB']->query_select_value_if_there('cached_comcode_pages','cc_page_title',array('the_page'=>$child['the_page'],'the_zone'=>$child['the_zone']));
					if (!is_null($_title))
					{
						$title=get_translated_text($_title);
					}
				}
			}

			if ($title=='')
				$title=escape_html(titleify($child['the_page']));

			$child['TITLE']=$title;
			$child['PAGE']=$child['the_page'];
			$child['ZONE']=$child['the_zone'];

			$children[$i]=$child;
		}

		$GLOBALS['M_SORT_KEY']='TITLE';
		usort($children,'multi_sort');

		return do_template('BLOCK_MAIN_COMCODE_PAGE_CHILDREN',array('_GUID'=>'375aa1907fc6b2ca6b23ab5b5139aaef','CHILDREN'=>$children,'THE_PAGE'=>$page,'THE_ZONE'=>$zone));
	}

}

