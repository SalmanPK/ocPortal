<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

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

class Block_main_gallery_embed
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
		$info['parameters']=array('param','select','video_select','zone','max','title');
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
		$info['cache_on']='array(get_param_integer(\'mge_start\',0),$GLOBALS[\'FORUM_DRIVER\']->get_members_groups(get_member(),false,true),array_key_exists(\'param\',$map)?$map[\'param\']:db_get_first_id(),array_key_exists(\'zone\',$map)?$map[\'zone\']:\'\',((is_null($map)) || (!array_key_exists(\'select\',$map)))?\'*\':$map[\'select\'],((is_null($map)) || (!array_key_exists(\'video_select\',$map)))?\'*\':$map[\'video_select\'],get_param_integer(\'mge_max\',array_key_exists(\'max\',$map)?intval($map[\'max\']):30),array_key_exists(\'title\',$map)?$map[\'title\']:\'\')';
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
		require_css('galleries');
		require_lang('galleries');
		require_code('galleries');
		require_code('images');
		require_code('feedback');

		require_code('ocfiltering');
		$cat=array_key_exists('param',$map)?$map['param']:'root';
		$cat_select=ocfilter_to_sqlfragment($cat,'cat','galleries','parent_id','cat','name',false,false);

		$title=array_key_exists('title',$map)?$map['title']:'';

		/*if ((file_exists(get_custom_file_base().'/themes/default/templates_custom/JAVASCRIPT_GALLERIES.tpl')) || (file_exists(get_file_base().'/themes/default/templates/JAVASCRIPT_GALLERIES.tpl')))
			require_javascript('javascript_galleries');*/

		$max=get_param_integer('mge_max',array_key_exists('max',$map)?intval($map['max']):30);
		$start=get_param_integer('mge_start',0);

		if (!array_key_exists('select',$map)) $map['select']='*';
		$image_select=ocfilter_to_sqlfragment($map['select'],'id');

		if (!array_key_exists('video_select',$map)) $map['video_select']='*';
		$video_select=ocfilter_to_sqlfragment($map['video_select'],'id');

		$zone=array_key_exists('zone',$map)?$map['zone']:get_module_zone('galleries');

		$rows_images=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'images WHERE ('.$cat_select.') AND ('.$image_select.') ORDER BY id DESC',$max+$start);
		$rows_videos=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'videos WHERE ('.$cat_select.') AND ('.$video_select.') ORDER BY id DESC',$max+$start);

		$total_images=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.get_table_prefix().'images WHERE ('.$cat_select.') AND ('.$image_select.')');
		$total_videos=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.get_table_prefix().'videos WHERE ('.$cat_select.') AND ('.$video_select.')');

		// Sort
		$combined=array();
		foreach ($rows_images as $row_image) $combined[]=array($row_image,'image',$row_image['add_date']);
		foreach ($rows_videos as $row_video) $combined[]=array($row_video,'video',$row_video['add_date']);
		global $M_SORT_KEY;
		$M_SORT_KEY=2;
		usort($combined,'multi_sort');
		$combined=array_reverse($combined);

		// Display
		$entries=new ocp_tempcode();
		foreach ($combined as $i=>$c)
		{
			if ($i>=$start)
			{
				switch ($c[1])
				{
					case 'image':
						// Display image
						$row_image=$c[0];
						$view_url=build_url(array('page'=>'galleries','type'=>'image','wide'=>1,'id'=>$row_image['id']),$zone);
						$thumb_url=ensure_thumbnail($row_image['url'],$row_image['thumb_url'],'galleries','images',$row_image['id']);
						$thumb=do_image_thumb($thumb_url,'',true);
						$full_url=$row_image['url'];
						$file_size=url_is_local($full_url)?strval(filesize(get_custom_file_base().'/'.rawurldecode($full_url))):'';
						if (url_is_local($full_url)) $full_url=get_custom_base_url().'/'.$full_url;
						$thumb_url=$row_image['thumb_url'];
						if (url_is_local($thumb_url)) $thumb_url=get_custom_base_url().'/'.$thumb_url;

						$entry_rating_details=($row_image['allow_rating']==1)?display_rating('images',strval($row_image['id'])):NULL;

						$entry_map=array('_GUID'=>'043ac7d15ce02715ac02309f6e8340ff','RATING_DETAILS'=>$entry_rating_details,'DESCRIPTION'=>get_translated_tempcode($row_image['comments']),'ID'=>strval($row_image['id']),'FILE_SIZE'=>$file_size,'SUBMITTER'=>strval($row_image['submitter']),'FULL_URL'=>$full_url,'THUMB_URL'=>$thumb_url,'CAT'=>$cat,'THUMB'=>$thumb,'VIEW_URL'=>$view_url,'VIEWS'=>strval($row_image['image_views']),'ADD_DATE_RAW'=>strval($row_image['add_date']),'EDIT_DATE_RAW'=>is_null($row_image['edit_date'])?'':strval($row_image['edit_date']));
						$entry=do_template('GALLERY_IMAGE',$entry_map);
						$entries->attach(do_template('GALLERY_ENTRY_WRAP',array('_GUID'=>'13134830e1ebea158ab44885eeec0953','ENTRY'=>$entry)+$entry_map));

						break;

					case 'video':
						// Display video
						$row_video=$c[0];
						$view_url=build_url(array('page'=>'galleries','type'=>'video','wide'=>1,'id'=>$row_video['id']),$zone);
						$thumb_url=$row_video['thumb_url'];
						if (($thumb_url!='') && (url_is_local($thumb_url))) $thumb_url=get_custom_base_url().'/'.$thumb_url;
						if ($thumb_url=='') $thumb_url=find_theme_image('na');
						$thumb=do_image_thumb($thumb_url,'',true);
						$full_url=$row_video['url'];
						if (url_is_local($full_url)) $full_url=get_custom_base_url().'/'.$full_url;
						$thumb_url=$row_video['thumb_url'];
						if (($thumb_url!='') && (url_is_local($thumb_url))) $thumb_url=get_custom_base_url().'/'.$thumb_url;

						$entry_rating_details=($row_video['allow_rating']==1)?display_rating('videos',strval($row_video['id'])):NULL;

						$entry_map=array('_GUID'=>'66b7fb4d3b61ef79d6803c170d102cbf','RATING_DETAILS'=>$entry_rating_details,'DESCRIPTION'=>get_translated_tempcode($row_video['comments']),'ID'=>strval($row_video['id']),'CAT'=>$cat,'THUMB'=>$thumb,'VIEW_URL'=>$view_url,'SUBMITTER'=>strval($row_video['submitter']),'FULL_URL'=>$full_url,'THUMB_URL'=>$thumb_url,'VIDEO_DETAILS'=>show_video_details($row_video),'VIEWS'=>strval($row_video['video_views']),'ADD_DATE_RAW'=>strval($row_video['add_date']),'EDIT_DATE_RAW'=>is_null($row_video['edit_date'])?'':strval($row_video['edit_date']));
						$entry=do_template('GALLERY_VIDEO',$entry_map);
						$entries->attach(do_template('GALLERY_ENTRY_WRAP',array('_GUID'=>'a0ff010ae7fd1f7b3341993072ed23cf','ENTRY'=>$entry)+$entry_map));
				
						break;
				}
			}

			$i++;
			if ($i==$start+$max) break;
		}

		if ($entries->is_empty())
		{
			if ((has_actual_page_access(NULL,'cms_galleries',NULL,NULL)) && (has_submit_permission('mid',get_member(),get_ip_address(),'cms_galleries',array('galleries',$cat))))
			{
				$submit_url=build_url(array('page'=>'cms_galleries','type'=>'ad','cat'=>$cat,'redirect'=>SELF_REDIRECT),get_module_zone('cms_galleries'));
			} else $submit_url=new ocp_tempcode();
			return do_template('BLOCK_NO_ENTRIES',array('_GUID'=>'bf84d65b8dd134ba6cd7b1b7bde99de2','HIGH'=>false,'TITLE'=>do_lang_tempcode('GALLERY'),'MESSAGE'=>do_lang_tempcode('NO_ENTRIES'),'ADD_NAME'=>do_lang_tempcode('ADD_IMAGE'),'SUBMIT_URL'=>$submit_url));
		}

		// Results browser
		require_code('templates_results_browser');
		$_selectors=array_map('intval',explode(',',get_option('gallery_selectors')));
		$root=get_param('root','root');
		$results_browser=results_browser(do_lang('ENTRY'),$cat,$start,'mge_start',$max,'mge_max',$total_videos+$total_images,$root,'misc',true,false,10,$_selectors);

		$tpl=do_template('BLOCK_MAIN_GALLERY_EMBED',array('_GUID'=>'b7b969c8fe8c398dd6e3af7ee06717ea','BLOCK_PARAMS'=>block_params_arr_to_str($map),'RESULTS_BROWSER'=>$results_browser,'TITLE'=>$title,'CAT'=>$cat,'IMAGES'=>$entries,'MAX'=>strval($max),'ZONE'=>$zone,'TOTAL_VIDEOS'=>strval($total_videos),'TOTAL_IMAGES'=>strval($total_images),'TOTAL'=>strval($total_videos+$total_images)));
		return $tpl;
	}

}


