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
 * @package		gallery_syndication
 */

function init__gallery_syndication()
{
	define('SYNDICATION_ORPHANS__LEAVE',1);
	define('SYNDICATION_ORPHANS__UNVALIDATE',2);
	define('SYNDICATION_ORPHANS__DELETE',3);

	$filter=get_option('gallery_sync_ocfilter',true);
	if (is_null($filter))
	{
		add_config_option('GALLERY_SYNC_OCFILTER','gallery_sync_ocfilter','line','return \'\';','FEATURE','GALLERY_SYNDICATION');
		add_config_option('GALLERY_SYNC_ORPHANED_HANDLING','gallery_sync_orphaned_handling','line','return \'\';','FEATURE','GALLERY_SYNDICATION');
		add_config_option('VIDEO_SYNC_TRANSCODING','video_sync_transcoding','special','return \''.addcslashes(do_lang('OTHER',NULL,NULL,NULL,fallback_lang())).'\';','FEATURE','GALLERY_SYNDICATION');
	}
}

function sync_video_syndication($local_id=NULL,$reupload=false)
{
	$orphaned_handling=get_option('gallery_sync_orphaned_handling');

	if (is_null($local_id)) // If being asked to do a full sync
	{
		$num_local_videos=$GLOBALS['SITE_DB']->query_select_value('videos','COUNT(*)');
		if ($num_local_videos>1000)
		{
			return; // Too much, we won't do complex two-way syncs
		}
	}

	$local_videos=get_local_videos($local_id);

	$hooks=find_all_hooks('modules','gallery_syndication');

	// What is already on remote server
	foreach (array_keys($hooks) as $hook)
	{
		$exists_remote=array();

		require_code('hooks/modules/video_syndication');
		$ob=object_factory('video_syndication_'.filter_naughty($hook));
		if ($ob->is_active())
		{
			$remote_videos=$ob->get_remote_videos($local_id);
			foreach ($remote_videos as $video)
			{
				_sync_remote_video($ob,$video,$local_videos,$orphaned_handling,$reupload);
				$exists_remote[$video['bound_to_local_id']]=true;
			}
		}

		// What is there locally
		foreach ($local_videos as $video)
		{
			if (!array_key_exists($video['local_id'],$exists_remote))
			{
				_sync_onlylocal_video($ob,$video);
			}
		}
	}
}

function get_local_videos($local_id=NULL)
{
	$videos=array();

	$filter=get_option('gallery_sync_ocfilter');

	if ($filter=='')
	{
		$where='1=1';
	} else
	{
		require_code('ocfiltering');
		$where=ocfilter_to_sqlfragment($filter,'id','galleries','parent_id','cat','name',true,false);
	}

	if (!is_null($local_id)) $where.=' AND id='.strval($local_id);

	$rows=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'videos WHERE '.$where);
	foreach ($rows as $row)
	{
		$videos[$row['id']]=_get_local_video($row,$tree);
	}

	return $videos;
}

function _get_local_video($row)
{
	static $tree=NULL;
	if ($tree===NULL)
	{
		$num_galleries=$GLOBALS['SITE_DB']->query_select_value('galleries','COUNT(*)');
		if ($num_galleries<500)
		{
			$tree=collapse_2d_complexity('cat','parent_id',$GLOBALS['SITE_DB']->query_select('galleries',array('cat','parent_id')));
		} else
		{
			$tree=array(); // Pull in dynamically via individual queries
		}
	}

	$categories=array($row['cat']);
	$parent_id=$row['parent_id'];
	while (!is_null($parent_id))
	{
		array_push($categories,$parent_id);
		if (array_key_exists($parent_id,$tree))
		{
			$parent_id=$tree[$parent_id];
		} else
		{
			$parent_id=$GLOBALS['SITE_DB']->query_select_value_if_there('galleries','parent_id',array('cat'=>$tree[$parent_id]));
		}
	}
	array_pop($categories); // We don't need root category on there

	$url=$row['url'];
	if (url_is_local($url)) $url=get_custom_base_url().'/'.$url;

	$tags=array_reverse($categories);
	list($keywords,)=seo_meta_get_for('video',$row['id']);
	if ($keywords!='')
	{
		$tags=array_unique(array_merge($tags,array_map('trim',explode(',',$keywords))));
	}

	return array(
		'local_id'=>$row['id'],
		'title'=>get_translated_text($row['title']),
		'description'=>comcode_to_clean_text(get_translated_text($row['description'])),
		'mtime'=>is_null($row['edit_date'])?$row['add_date']:$row['edit_date'],
		'tags'=>$tags,
		'url'=>$url,
		'_raw_url'=>$row['url'],
		'_thumb_url'=>$row['thumb_url'],
		'allow_rating'=>($row['allow_rating']==1),
		'allow_comments'=>($row['allow_comments']==1),
		'validated'=>($row['validated']==1),
	);
}

function _sync_remote_video($ob,$video,$local_videos,$orphaned_handling,$reupload)
{
	if (array_key_exists($video['bound_to_local_id'],$local_videos))
	{
		$local_video=$local_videos[$video['bound_to_local_id']];

		// Handle changes
		$changes=array();
		foreach (array('title','description','tags','allow_rating','allow_comments','validated') as $property)
		{
			if ((!is_null($video[$property])) && ($video[$property]!=$local_video[$property]))
			{
				$changes[$property]=$local_video[$property];
			}
		}
		if ($reupload)
		{
			$changes+=array('url'=>$local_video['url']);
		}
		if ($changes!=array())
			$ob->change_remote_video($video,$changes);
	} else
	{
		// Orphaned remotes
		switch ($orphaned_handling)
		{
			case SYNDICATION_ORPHANS__LEAVE:
				$ob->unbind_remote_video($video);
				break;

			case SYNDICATION_ORPHANS__UNVALIDATE:
				$ob->unbind_remote_video($video);
				$ob->change_remote_video($video,array('validated'=>false));
				break;

			case SYNDICATION_ORPHANS__DELETE:
				$ob->delete_remote_video($video);
				break;
		}
	}
}

function _sync_onlylocal_video($ob,$local_video)
{
	$remote_video=$ob->upload_video($local_video);
	if (is_null($remote_video)) return; // Didn't work, can't do anything further

	$service_name=preg_replace('#^video\_syndication\_#','',get_class($ob));
	$service_title=$ob->get_service_title();

	$local_video_url=build_url(array('page'=>'galleries','type'=>'video','id'=>$local_video['local_id']),get_module_zone('galleries'),NULL,false,false,true);

	require_lang('gallery_syndication');
	$comment=do_lang('VIDEO_SYNC_INITIAL_COMMENT',$service_title,get_site_name(),$local_video_url->evaluate());

	if ($comment!='')
		$ob->leave_comment($remote_video,$comment);

	// Store the DB mapping for the transcoding
	$transcoding_id=$service_name.'_'.strval($remote_video['remote_id']);
	$GLOBALS['SITE_DB']->query_insert('video_transcoding',array(
		't_id'=>$transcoding_id,
		't_local_id'=>$local_video['local_id'],
		't_local_id_field'=>'id',
		't_error'=>'',
		't_url'=>$local_video['_raw_url'],
		't_table'=>'videos',
		't_url_field'=>'url',
		't_orig_filename_field'=>'',
		't_width_field'=>'video_width',
		't_height_field'=>'video_height',
		't_output_filename'=>'',
	));

	if (get_option('video_sync_transcoding')==$service_name)
	{
		require_lang('galleries');
		attach_message(do_lang_tempcode('TRANSCODING_IN_PROGRESS'),'inform');

		// Actually carry over the DB mapping as an actual transcoding
		require_code('transcoding');
		store_transcoding_success($transcoding_id,$remote_video['url']);

		// Now copy over thumbnail, if applicable
		if (find_theme_image('video_thumb',true)===$local_video['_thumb_url']) // Is currently on default thumb (i.e. none explicitly chosen)
		{
			require_code('galleries2');
			$thumb_url=create_video_thumb($remote_video['url']);
			$GLOBALS['SITE_DB']->query_update('videos',array('thumb_url'=>$thumb_url),array('id'=>$local_video['local_id']),'',1);
		}
	}
}