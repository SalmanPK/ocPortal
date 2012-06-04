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

class Block_main_cc_embed
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
		$info['parameters']=array('root','sort','search','max','param','select','template_set','display_type');
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
		$info['cache_on']='array(((array_key_exists(\'root\',$map)) && ($map[\'root\']!=\'\'))?intval($map[\'root\']):get_param_integer(\'root\',NULL),array_key_exists(\'search\',$map)?$map[\'search\']:\'\',array_key_exists(\'sort\',$map)?$map[\'sort\']:\'\',array_key_exists(\'display_type\',$map)?$map[\'display_type\']:NULL,array_key_exists(\'template_set\',$map)?$map[\'template_set\']:\'\',array_key_exists(\'select\',$map)?$map[\'select\']:\'\',array_key_exists(\'param\',$map)?$map[\'param\']:db_get_first_id(),get_param_integer(\'max\',array_key_exists(\'max\',$map)?intval($map[\'max\']):30),get_param_integer(\'start\',0))';
		$info['ttl']=60*2;
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
		$category_id=array_key_exists('param',$map)?intval($map['param']):db_get_first_id();
		$max=get_param_integer('max',array_key_exists('max',$map)?intval($map['max']):30);
		$start=get_param_integer('start',0);
		$root=((array_key_exists('root',$map)) && ($map['root']!=''))?intval($map['root']):get_param_integer('root',NULL);

		$sort=array_key_exists('sort',$map)?$map['sort']:'';
		$search=array_key_exists('search',$map)?$map['search']:'';

		require_lang('catalogues');
		require_code('catalogues');
		require_code('feedback');
		require_css('catalogues');

		$select=NULL;
		if ((!is_null($map)) && (array_key_exists('select',$map)))
		{
			require_code('ocfiltering');
			$select=ocfilter_to_sqlfragment($map['select'],'e.id','catalogue_categories','cc_parent_id','cc_id','id');
		}

		$categories=$GLOBALS['SITE_DB']->query_select('catalogue_categories',array('*'),array('id'=>$category_id),'',1);
		if (!array_key_exists(0,$categories))
		{
			return do_lang_tempcode('MISSING_RESOURCE');
		}
		$category=$categories[0];

		$catalogue_name=$category['c_name'];
		$catalogues=$GLOBALS['SITE_DB']->query_select('catalogues',array('*'),array('c_name'=>$catalogue_name),'',1);
		$catalogue=$catalogues[0];

		$tpl_set=array_key_exists('template_set',$map)?$map['template_set']:$catalogue_name;
		$display_type=array_key_exists('display_type',$map)?intval($map['display_type']):NULL;

		list($entry_buildup,,,)=get_catalogue_category_entry_buildup(is_null($select)?$category_id:NULL,$catalogue_name,$catalogue,'CATEGORY',$tpl_set,$max,$start,$select,$root,$display_type,true,NULL,$search,$sort);

		return do_template('CATALOGUE_'.$tpl_set.'_CATEGORY_EMBED',array('ROOT'=>strval($root),'CATALOGUE'=>$catalogue_name,'ENTRIES'=>$entry_buildup),NULL,false,'CATALOGUE_DEFAULT_CATEGORY_EMBED');
	}

}


