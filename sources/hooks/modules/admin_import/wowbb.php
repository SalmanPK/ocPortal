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
 * @package		import
 */

/**
 * Standard code module initialisation function.
 */
function init__hooks__modules__admin_import__wowbb()
{
	global $TOPIC_FORUM_CACHE;
	$TOPIC_FORUM_CACHE=array();

	global $STRICT_FILE;
	$STRICT_FILE=false; // Disable this for a quicker import that is quite liable to go wrong if you don't have the files in the right place

	global $OLD_BASE_URL;
	$OLD_BASE_URL=NULL;
}

class Hook_wowbb
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['supports_advanced_import']=false;
		$info['product']='WowBB 1.65';
		$info['prefix']='wowbb_';
		$info['import']=array(
								'ocf_groups',
								'ocf_members',
								'ocf_categories',
								'ocf_forums',
								'ocf_topics',
								'ocf_posts',
								'ocf_polls_and_votes',
								'notifications',
								'ocf_personal_topics',
								'ocf_post_files',
								'calendar',
								'wordfilter',
								'ip_bans',
								'config',
							);
		$info['dependencies']=array( // This dependency tree is overdefined, but I wanted to make it clear what depends on what, rather than having a simplified version
								'ocf_members'=>array('ocf_groups'),
								'ocf_forums'=>array('ocf_categories','ocf_members','ocf_groups'),
								'ocf_topics'=>array('ocf_forums','ocf_members'),
								'ocf_polls_and_votes'=>array('ocf_topics','ocf_members'),
								'ocf_posts'=>array('ocf_topics','ocf_members'),
								'ocf_post_files'=>array('ocf_posts','ocf_personal_topics'),
								'notifications'=>array('ocf_topics','ocf_members'),
								'ocf_personal_topics'=>array('ocf_members'),
								'calendar'=>array('ocf_members'),
							);
		$_cleanup_url=build_url(array('page'=>'admin_cleanup'),get_module_zone('admin_cleanup'));
		$cleanup_url=$_cleanup_url->evaluate();
		$info['message']=(get_param('type','misc')!='import' && get_param('type','misc')!='hook')?new ocp_tempcode():do_lang_tempcode('FORUM_CACHE_CLEAR',escape_html($cleanup_url));

		return $info;
	}

	/**
	 * Probe a file path for DB access details.
	 *
	 * @param  string			The probe path
	 * @return array			A quartet of the details (db_name, db_user, db_pass, table_prefix)
	 */
	function probe_db_access($file_base)
	{
		global $HTTP_SERVER_VARS;
		global $HTTP_COOKIE_VARS;
		global $HTTP_ENV_VARS;
		global $HTTP_GET_VARS;
		global $HTTP_POST_VARS;
		global $HTTP_POST_FILES;
		global $HTTP_SESSION_VARS;
		if (!file_exists($file_base.'/config.php'))
			warn_exit(do_lang_tempcode('BAD_IMPORT_PATH',escape_html('config.php')));
		require($file_base.'/config.php');
		$INFO=array();
		$INFO['sql_database']=constant('DB_NAME');
		$INFO['sql_user']=constant('DB_USER_NAME');
		$INFO['sql_pass']=constant('DB_PASSWORD');
		$INFO['sql_tbl_prefix']='wowbb_';

		return array($INFO['sql_database'],$INFO['sql_user'],$INFO['sql_pass'],$INFO['sql_tbl_prefix']);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_config($db,$table_prefix,$file_base)
	{
		$config_remapping=array(
			'ACTIVATION_EMAIL'=>'require_new_member_validation',
			'BOARD_ON'=>'!site_closed',
			//'MANA'=>'is_on_points',
			'BOARD_NAME'=>'site_name',
			'SESSION_LENGTH'=>'session_expiry_time',
			'POSTS_PER_PAGE'=>'forum_posts_per_page',
			'TOPICS_PER_PAGE'=>'forum_topics_per_page',
			'BOARD_EMAIL'=>'staff_address',
			'SE_FRIENDLY_URLS'=>'mod_rewrite',
			'ATTACHMENT_ALLOWED_EXTENSIONS'=>'valid_types',
		);

		require($file_base.'/config.php');
		foreach ($config_remapping as $from=>$remapping)
		{
			$value=constant($from);

			if ($from=='SESSION_LENGTH')
			{
				$value=strval(intval(ceil(floatval($value)/60.0)));
			}
			if ($from=='ATTACHMENT_ALLOWED_EXTENSIONS')
			{
				$temp=explode(' ',$value);
				$temp+=explode(',',get_option('valid_types'));
				$value=implode(',',array_unique($temp));
			}

			if ($remapping[0]=='!')
			{
				$remapping=substr($remapping,1);
				$value=strval(1-intval($value));
			}
			set_option($remapping,$value);
		}

		set_value('timezone',constant('SERVER_TIME_ZONE'));

		$page_remap=array('NEW_REGISTRATIONS'=>'join');
		foreach ($page_remap as $to)
		{
			$GLOBALS['SITE_DB']->query_delete('group_page_access',array('page_name'=>$to,'zone_name'=>get_module_zone($to)));
		}

		// Now some usergroup options
		$groups=$GLOBALS['OCF_DRIVER']->get_usergroup_list();
		foreach (array_keys($groups) as $id)
		{
			if ($GLOBALS['OCF_DRIVER']->is_super_admin($id)) continue;

			$GLOBALS['FORUM_DB']->query_update('f_groups',array('g_max_avatar_width'=>constant('AVATAR_DIMENSIONS_MAX'),'g_max_avatar_height'=>constant('AVATAR_DIMENSIONS_MAX'),'g_flood_control_access_secs'=>constant('FLOOD_INTERVAL')),array('id'=>$id),'',1);

			foreach ($page_remap as $from=>$to)
			{
				if (constant($from)==1)
					$GLOBALS['SITE_DB']->query_insert('group_page_access',array('page_name'=>$to,'zone_name'=>get_module_zone($to),'group_id'=>$id));
			}
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_groups($db,$table_prefix,$file_base)
	{
		require($file_base.'/config.php');

		$rows=$db->query('SELECT * FROM '.$table_prefix.'user_groups ORDER BY user_group_id');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('group',strval($row['user_group_id']))) continue;

			$is_super_admin=$row['admin_rights'];
			$is_super_moderator=$row['super_moderator_rights'];

			$group_name_remap=array('Unregistered'=>'Guests','Moderators'=>'Super-members','Super Moderators'=>'Super-moderators');
			if (array_key_exists($row['user_group_name'],$group_name_remap)) $row['user_group_name']=$group_name_remap[$row['user_group_name']];

			$id_new=$GLOBALS['FORUM_DB']->query_value_null_ok('f_groups g LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON g.g_name=t.id WHERE '.db_string_equal_to('text_original',$row['user_group_name']),'g.id');
			if (is_null($id_new))
			{
				$id_new=ocf_make_group($row['user_group_name'],0,$is_super_admin,$is_super_moderator,$row['user_group_title'],'',NULL,NULL,NULL,constant('FLOOD_INTERVAL'),0,($row['post_attachments']==0)?0:5,5,constant('AVATAR_DIMENSIONS_MAX'),constant('AVATAR_DIMENSIONS_MAX'),30000,700,25,$row['add_mana']);
			}

			// privileges
			set_specific_permission($id_new,'vote_in_polls',$row['vote_on_polls']);
			set_specific_permission($id_new,'use_pt',$row['pm']);
			set_specific_permission($id_new,'submit_lowrange_content',$row['post_new_topics']);
			set_specific_permission($id_new,'view_member_photos',$row['view_member_info']);
			set_specific_permission($id_new,'edit_lowrange_content',$row['edit_own_posts']);
			set_specific_permission($id_new,'add_public_events',$row['post_public_events']);
			set_specific_permission($id_new,'view_calendar',$row['view_public_events']);

			$denies=array();
			if ($row['view_board']==0) $denies[]=array('forumview',get_module_zone('forumview'));
			if ($row['search']==0) $denies[]=array('search',get_module_zone('search'));
			foreach ($denies as $deny)
			{
				list($page,$zone)=$deny;
				$test=$GLOBALS['SITE_DB']->query_value_null_ok('group_page_access','group_id',array('group_id'=>$id_new,'zone_name'=>$zone,'page_name'=>$page));
				if (is_null($test)) $GLOBALS['SITE_DB']->query_insert('group_page_access',array('group_id'=>$id_new,'zone_name'=>$zone,'page_name'=>$page));
			}

			import_id_remap_put('group',strval($row['user_group_id']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_members($db,$table_prefix,$file_base)
	{
		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'users ORDER BY user_id',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('member',strval($row['user_id']))) continue;

				$test=$GLOBALS['OCF_DRIVER']->get_member_from_username($row['user_name']);
				if (!is_null($test))
				{
					import_id_remap_put('member',strval($row['user_id']),$test);
					continue;
				}

				$language='';
				if ($row['user_language']!='')
				{
					switch ($language) // Can be extended as needed
					{
						case 'english':
							$language='EN';
							break;
					}
				}

				$primary_group=import_id_remap_get('group',$row['user_group_id']);
				$secondary_groups=array();

				$custom_fields=array(
										ocf_make_boiler_custom_field('im_icq')=>$row['user_icq'],
										ocf_make_boiler_custom_field('im_aim')=>$row['user_aim'],
										ocf_make_boiler_custom_field('im_msn')=>$row['user_msnm'],
										ocf_make_boiler_custom_field('im_yahoo')=>$row['user_ym'],
										ocf_make_boiler_custom_field('interests')=>$row['user_interests'],
										ocf_make_boiler_custom_field('location')=>$row['user_city'].','.$row['user_region'].','.$row['user_country'],
										ocf_make_boiler_custom_field('occupation')=>$row['user_occupation'],
									);
				if ($row['user_homepage']!='')
					$custom_fields[ocf_make_boiler_custom_field('website')]=(strlen($row['user_homepage'])>0)?('[url]'.$row['user_homepage'].'[/url]'):'';

				$signature=$this->fix_links($row['user_signature'],$db,$table_prefix,$file_base);
				$validated=($row['user_activation_key']=='')?1:0;
				$reveal_age=0;
				$exp=explode('-',$row['user_birthday']);
				list($bday_day,$bday_month,$bday_year)=array($exp[2],$exp[1],$exp[0]);
				$views_signatures=1;
				$preview_posts=1;
				$title='';
				$photo_url='';
				$photo_thumb_url='';

				$avatar_url=$row['user_avatar'];
				if (substr($avatar_url,0,strlen('images/avatars/galleries/'))=='images/avatars/galleries/')
				{
					$avatar_url=str_replace('images/avatars/galleries/','themes/default/images/ocf_default_avatars/',$avatar_url);
				} else $avatar_url=str_replace('images/avatars/','uploads/ocf_avatars/',$avatar_url);

				$password=$row['user_password'];
				$type='md5';
				$salt='';

				$id_new=ocf_make_member($row['user_name'],$password,$row['user_email'],NULL,$bday_day,$bday_month,$bday_year,$custom_fields,NULL,$primary_group,$validated,strtotime($row['user_joined']),strtotime($row['user_joined']),'',$avatar_url,$signature,0,$preview_posts,$reveal_age,$title,$photo_url,$photo_thumb_url,$views_signatures,0,$language,1,$row['user_admin_emails'],'','','',false,$type,$salt,1);

				// Fix usergroup leadership
				$GLOBALS['FORUM_DB']->query_update('f_groups',array('g_group_leader'=>$id_new),array('g_group_leader'=>-$row['user_id']));

				import_id_remap_put('member',strval($row['user_id']),$id_new);

				// Set up usergroup membership
				foreach ($secondary_groups as $s)
				{
					list($group,$userpending)=$s;
					ocf_add_member_to_group($id_new,$group,1-$userpending);
				}

				// OCP fields
				foreach ($row as $field=>$val)
				{
					if (substr($val,0,4)=='ocp_')
						$GLOBALS['OCF_DRIVER']->set_custom_field($id_new,$field,substr($val,4));
				}
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ip_bans($db,$table_prefix,$file_base)
	{
		require($file_base.'/config.php');
		$ips=constant('BANNED_IPS');

		if ($ips=='Array') return;
		$rows=explode('|',$ips);

		require_code('failure');

		foreach ($rows as $row)
		{
			if (import_check_if_imported('ip_ban',$row)) continue;

			add_ip_ban($row);

			import_id_remap_put('ip_ban',$row,0);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_categories($db,$table_prefix,$old_base_dir)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'categories');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('category',strval($row['category_id']))) continue;

			$title=$row['category_name'];

			$test=$GLOBALS['FORUM_DB']->query_value_null_ok('f_categories','id',array('c_title'=>$title));
			if (!is_null($test))
			{
				import_id_remap_put('category',strval($row['category_id']),$test);
				continue;
			}

			$id_new=ocf_make_category($title,'',1);

			import_id_remap_put('category',strval($row['category_id']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_forums($db,$table_prefix,$old_base_dir)
	{
		require_code('ocf_forums_action2');

		$rows=$db->query('SELECT * FROM '.$table_prefix.'forums');
		foreach ($rows as $row)
		{
			$remapped=import_id_remap_get('forum',strval($row['forum_id']),true);
			if (!is_null($remapped))
			{
				continue;
			}

			$name=$row['forum_name'];
			$description=html_to_comcode($row['forum_description']);
			$position=$row['forum_order'];
			$post_count_increment=1;

			$category_id=import_id_remap_get('category',strval($row['category_id']),true);
			$parent_forum=db_get_first_id();

			$access_mapping=array();
			$permissions=$db->query('SELECT * FROM '.$table_prefix.'forum_permissions WHERE forum_id='.strval((integer)$row['forum_id']));
			foreach ($permissions as $p)
			{
				$v=0;
				if ($p['view_others_topics']==1) $v=1;
				if ($p['reply_to_others_topics']==1) $v=2;
				if ($p['post_new_topics']==1) $v=3;
				if ($p['post_polls']==1) $v=4; // This ones a bit hackerish, but closest we can get to concept
				$group_id=import_id_remap_get('group',strval($p['user_group_id']),true);
				if (!is_null($group_id))
					$access_mapping[$group_id]=$v;
			}

			$id_new=ocf_make_forum($name,$description,$category_id,$access_mapping,$parent_forum,$position,$post_count_increment,0,'');

			import_id_remap_put('forum',strval($row['forum_id']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_topics($db,$table_prefix,$file_base)
	{
		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'topics WHERE topic_redirects_to=0 ORDER BY topic_id',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('topic',strval($row['topic_id']))) continue;

				$forum_id=import_id_remap_get('forum',strval($row['forum_id']));

				$id_new=ocf_make_topic($forum_id,$row['topic_description'],'',1,($row['topic_status']==1)?0:1,($row['topic_type']>0)?1:0,0,($row['topic_type']>2)?1:0,NULL,NULL,false,$row['topic_views']);

				import_id_remap_put('topic',strval($row['topic_id']),$id_new);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_posts($db,$table_prefix,$file_base)
	{
		global $STRICT_FILE;

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'posts p LEFT JOIN '.$table_prefix.'post_texts t ON p.post_id=t.post_id ORDER BY p.post_id',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('post',strval($row['post_id']))) continue;

				$topic_id=import_id_remap_get('topic',strval($row['topic_id']),true);
				if (is_null($topic_id))
				{
					import_id_remap_put('post',strval($row['post_id']),-1);
					continue;
				}
				$member_id=import_id_remap_get('member',strval($row['user_id']),true);
				if (is_null($member_id)) $member_id=db_get_first_id();

				$forum_id=import_id_remap_get('forum',strval($row['forum_id']),true);

				$title='';
				$test=$db->query_value('posts','MIN(post_id)',array('topic_id'=>$row['topic_id']));
				$first_post=$test==$row['post_id'];
				if ($first_post)
				{
					$topics=$db->query('SELECT topic_name,topic_starter_id FROM '.$table_prefix.'topics WHERE topic_id='.strval((integer)$row['topic_id']));
					$title=$topics[0]['topic_name'];
				}
				$post=$this->fix_links($row['post_text'],$db,$table_prefix,$file_base);

				$last_edit_by=($row['post_last_edited_by']==0)?NULL:import_id_remap_get('member',strval($row['post_last_edited_by']),true);
				$last_edit_time=strtotime($row['post_last_edited_on']);

				if ($row['post_user_name']=='') $row['post_user_name']=$GLOBALS['OCF_DRIVER']->get_username($member_id);

				$id_new=ocf_make_post($topic_id,$title,$post,0,$first_post,1,0,$row['post_user_name'],$row['post_ip'],strtotime($row['post_date_time']),$member_id,NULL,$last_edit_time,$last_edit_by,false,false,$forum_id,false);

				import_id_remap_put('post',strval($row['post_id']),$id_new);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Substitution callback for 'fix_links'.
	 *
	 * @param  array			The match
	 * @return  string		The substitution string
	 */
	function _fix_links_callback_topic($m)
	{
		return 'index.php?page=topicview&id='.strval(import_id_remap_get('topic',strval($m[2]),true));
	}

	/**
	 * Substitution callback for 'fix_links'.
	 *
	 * @param  array			The match
	 * @return  string		The substitution string
	 */
	function _fix_links_callback_forum($m)
	{
		return 'index.php?page=forumview&id='.strval(import_id_remap_get('forum',strval($m[2]),true));
	}

	/**
	 * Substitution callback for 'fix_links'.
	 *
	 * @param  array			The match
	 * @return  string		The substitution string
	 */
	function _fix_links_callback_member($m)
	{
		return 'index.php?page=members&type=view&id='.strval(import_id_remap_get('member',strval($m[2]),true));
	}

	/**
	 * Convert WowBB URLs pasted in text fields into ocPortal ones.
	 *
	 * @param  string			The text field text (e.g. a post)
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 * @return string			The new text field text
	 */
	function fix_links($post,$db,$table_prefix,$file_base)
	{
		require($file_base.'/config.php');
		$OLD_BASE_URL=constant('HOMEPAGE');

		$post=preg_replace_callback('#'.preg_quote($OLD_BASE_URL).'/(view_topic\.php\?id=)(\d*)&forum_id=(\d*)#',array($this,'_fix_links_callback_topic'),$post);
		$post=preg_replace_callback('#'.preg_quote($OLD_BASE_URL).'/(view_forum\.php\?id=)(\d*)#',array($this,'_fix_links_callback_forum'),$post);
		$post=preg_replace_callback('#'.preg_quote($OLD_BASE_URL).'/(view_user\.php\?id=)(\d*)#',array($this,'_fix_links_callback_member'),$post);
		$post=preg_replace('#:[0-9a-f]{10}#','',$post);
		$post=@html_entity_decode($post,ENT_QUOTES,get_charset());
		return $post;
	}

	/**
	 * Convert a WowBB database file to an ocPortal uploaded file (stored on disk).
	 *
	 * @param  string			The file data
	 * @param  string			The optimal filename
	 * @param  ID_TEXT		The upload type (e.g. ocf_photos)
	 * @return URLPATH		The URL
	 */
	function data_to_disk($data,$filename,$sections)
	{
		$filename=find_derivative_filename('uploads/'.$sections,$filename);
		$path=get_custom_file_base().'/uploads/'.$sections.'/'.$filename.'.dat';
		$myfile=@fopen($path,'wb') OR warn_exit(do_lang_tempcode('WRITE_ERROR',escape_html('uploads/'.$sections.'/'.$filename.'.dat')));
		if (fwrite($myfile,$data)<strlen($data)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
		fclose($myfile);
		fix_permissions($path);
		sync_file($path);

		$url='uploads/'.$sections.'/'.$filename.'.dat';

		return $url;
	}

	/**
	 * Standard import function. Note that this is designed for a very popular phpBB mod, and will exit silently if the mod hasn't been installed.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_post_files($db,$table_prefix,$file_base)
	{
		global $STRICT_FILE;
		require_code('attachments2');
		require_code('attachments3');

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'attachments',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('post_files',strval($row['attachment_id']))) continue;

				$post_id=$db->query_value_null_ok('posts','post_id',array('attachment_id'=>$row['attachment_id']));

				$post_row=array();
				if (!is_null($post_id))
				{
					$post_id=import_id_remap_get('post',$post_id);
					$post_row=$GLOBALS['FORUM_DB']->query_select('f_posts p LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON p.p_post=t.id',array('p_time','text_original','p_poster','p_post'),array('p.id'=>$post_id),'',1);
				}
				if (!array_key_exists(0,$post_row))
				{
					import_id_remap_put('post_files',strval($row['attachment_id']),1);
					continue; // Orphaned post
				}
				$post=$post_row[0]['text_original'];
				$lang_id=$post_row[0]['p_post'];
				$member_id=import_id_remap_get('member',strval($row['user_id']),true);
				if (is_null($member_id)) $member_id=$post_row[0]['p_poster'];

				$url=$this->data_to_disk($row['file_contents'],$row['file_name'],'attachments',false);
				$thumb_url='';

				$a_id=$GLOBALS['SITE_DB']->query_insert('attachments',array('a_member_id'=>$member_id,'a_file_size'=>strlen($row['file_contents']),'a_url'=>$url,'a_thumb_url'=>$thumb_url,'a_original_filename'=>$row['file_name'],'a_num_downloads'=>$row['downloads'],'a_last_downloaded_time'=>NULL,'a_add_time'=>strtotime($row['upload_date']),'a_description'=>''),true);

				$GLOBALS['SITE_DB']->query_insert('attachment_refs',array('r_referer_type'=>'ocf_post','r_referer_id'=>$post_id,'a_id'=>$a_id));
				$post.="\n\n".'[attachment=""]'.strval($a_id).'[/attachment]';

				ocf_over_msn();
				update_lang_comcode_attachments($lang_id,$post,'ocf_post',strval($post_id));
				ocf_over_local();

				import_id_remap_put('post_files',strval($row['attachment_id']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_polls_and_votes($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'poll_questions');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('poll',strval($row['poll_id']))) continue;

			$topic_id=import_id_remap_get('topic',strval($row['topic_id']),true);
			if (is_null($topic_id))
			{
				import_id_remap_put('poll',strval($row['poll_id']),-1);
				continue;
			}

			$is_open=$row['poll_ends']<time();

			$rows2=$db->query('SELECT * FROM '.$table_prefix.'poll_options WHERE poll_id='.strval((integer)$row['poll_id']).' ORDER BY poll_option_id');
			$answers=array();
			$answer_map=array();
			foreach ($rows2 as $answer)
			{
				$answer_map[$answer['poll_option_id']]=count($answers);
				$answers[]=$answer['option_text'];
			}
			$maximum=($row['multiple_choice']==1)?count($answers):1;

			$rows2=$db->query('SELECT * FROM '.$table_prefix.'vote_votes WHERE poll_id='.$row['poll_id']);
			foreach ($rows2 as $row2)
			{
				$row2['user_id']=import_id_remap_get('member',strval($row2['user_id']),true);
			}

			$id_new=ocf_make_poll($topic_id,$row['question'],0,$is_open?1:0,1,$maximum,0,$answers,false);

			$answers=collapse_1d_complexity('id',$GLOBALS['FORUM_DB']->query_select('f_poll_answers',array('id'),array('pa_poll_id'=>$id_new))); // Effectively, a remapping from IPB vote number to ocP vote number

			foreach ($rows2 as $row2)
			{
				$member_id=$row2['user_id'];
				if ((!is_null($member_id)) && ($member_id!=0))
				{
					if ($row2['poll_option_id']==0)
					{
						$answer=-1;
					} else
					{
						$answer=$answers[$answer_map[$row2['poll_option_id']]];
					}
					$GLOBALS['FORUM_DB']->query_insert('f_poll_votes',array('pv_poll_id'=>$id_new,'pv_member_id'=>$member_id,'pv_answer_id'=>$answer));
				}
			}

			import_id_remap_put('poll',strval($row['poll_id']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_personal_topics($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'pm p LEFT JOIN '.$table_prefix.'pm_texts t ON p.pm_id=t.pm_id ORDER BY pm_date_time');

		// Group them up into what will become topics
		$groups=array();
		$done=array();
		foreach ($rows as $row)
		{
			// Do some fiddling around for duplication
			if ($row['pm_from']>$row['pm_to'])
			{
				$a=$row['pm_to'];
				$b=$row['pm_from'];
			} else
			{
				$a=$row['pm_from'];
				$b=$row['pm_to'];
			}
			$row['pm_subject']=str_replace('Re: ','',$row['pm_subject']);
			if ($row['pm_subject']=='') $row['pm_subject']=do_lang('NONE');

			if (!array_key_exists(strval($a).':'.strval($b).':'.$row['pm_date_time'],$done))
			{
				$groups[strval($a).':'.strval($b).':'.$row['pm_subject']][]=$row;
				$done[strval($a).':'.strval($b).':'.$row['pm_date_time']]=1;
			}
		}

		// Import topics
		foreach ($groups as $group)
		{
			$row=$group[0];

			if (import_check_if_imported('pt',strval($row['pm_id']))) continue;

			// Create topic
			$from_id=import_id_remap_get('member',strval($row['pm_from']),true);
			if (is_null($from_id)) $from_id=$GLOBALS['OCF_DRIVER']->get_guest_id();
			$to_id=import_id_remap_get('member',strval($row['pm_to']),true);
			if (is_null($to_id)) $to_id=$GLOBALS['OCF_DRIVER']->get_guest_id();
			$topic_id=ocf_make_topic(NULL,'','',1,1,0,0,0,$from_id,$to_id,false);

			$first_post=true;
			foreach ($group as $_postdetails)
			{
				if ($first_post)
				{
					$title=$row['pm_subject'];
				} else $title='';

				$pos=strpos($_postdetails['pm_text'],'_____Original Message_____');
				if ($pos!==false) $_postdetails['pm_text']=substr($_postdetails['pm_text'],0,$pos);

				$post=$this->fix_links($_postdetails['pm_text'],$db,$table_prefix,$file_base);
				$validated=1;
				$from_id=import_id_remap_get('member',strval($_postdetails['pm_from']),true);
				if (is_null($from_id)) $from_id=$GLOBALS['OCF_DRIVER']->get_guest_id();
				$poster_name_if_guest=$GLOBALS['OCF_DRIVER']->get_username($from_id);
				$ip_address='127.0.0.1';
				$time=strtotime($_postdetails['pm_date_time']);
				$poster=$from_id;
				$last_edit_time=NULL;
				$last_edit_by=NULL;

				ocf_make_post($topic_id,$title,$post,0,$first_post,$validated,0,$poster_name_if_guest,$ip_address,$time,$poster,NULL,$last_edit_time,$last_edit_by,false,false,NULL,false);
				$first_post=false;
			}

			import_id_remap_put('pt',strval($row['pm_id']),$topic_id);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_notifications($db,$table_prefix,$file_base)
	{
		require_code('notifications');

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'notifications',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('topic_notification',strval($row['topic_id']).'-'.strval($row['user_id']))) continue;

				$member_id=import_id_remap_get('member',strval($row['user_id']),true);
				if (is_null($member_id)) continue;
				$topic_id=import_id_remap_get('topic',strval($row['topic_id']),true);
				if (is_null($topic_id)) continue;
				enable_notifications('ocf_topic',strval($topic_id),$member_id);

				import_id_remap_put('topic_notification',strval($row['topic_id']).'-'.strval($row['user_id']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'watched_forums',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('forum_notification',strval($row['user_id']))) continue;

				$member_id=import_id_remap_get('member',strval($row['user_id']),true);
				if (is_null($member_id)) continue;

				$forums=explode(',',$row['forum_ids']);
				foreach ($forums as $forum)
				{
					$forum_id=import_id_remap_get('forum',strval($forum),true);
					if (is_null($forum_id)) continue;
					enable_notifications('ocf_forum',strval($forum_id),$member_id);
				}

				import_id_remap_put('forum_notification',strval($row['user_id']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT user_id,user_visited_topics FROM '.$table_prefix.'users',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('has_read',strval($row['user_id']))) continue;

				$member_id=import_id_remap_get('member',strval($row['user_id']),true);
				if (is_null($member_id)) continue;

				$details=unserialize($row['user_visited_topics']);
				if (is_array($details))
				{
					foreach ($details as $topics)
					{
						if (is_array($topics))
						{
							foreach ($topics as $topic_id=>$time)
							{
								$topic_id=import_id_remap_get('topic',strval($topic_id),true);
								if (is_null($topic_id)) continue;
								$GLOBALS['FORUM_DB']->query_insert('f_read_logs',array('l_member_id'=>$member_id,'l_topic_id'=>$topic_id,'l_time'=>$time),false,true);
							}
						}
					}
				}

				import_id_remap_put('has_read',strval($row['user_id']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_wordfilter($db,$table_prefix,$file_base)
	{
		require($file_base.'/config.php');
		$filter=constant('BAD_WORD_FILTER');

		if ($filter=='Array') return;
		$rows=explode(' ',$filter);
		foreach ($rows as $row)
		{
			$pos=strpos($row,'=');
			if ($pos===false)
			{
				add_wordfilter_word($row);
			} else
			{
				add_wordfilter_word(substr($row,0,$pos),substr($row,$pos+1));
			}
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_calendar($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'calendar');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('event',strval($row['event_id']))) continue;

			$submitter=import_id_remap_get('member',strval($row['user_id']),true);
			if (is_null($submitter)) $submitter=$GLOBALS['OCF_DRIVER']->get_guest_id();

			$recurrence='none';
			$recurrences=NULL;
			if ($row['event_recurrence']==1)
			{
				$bits=explode('|',$row['event_recurrence_settings']); // $num_units, $every, $days_of_week (e.g. 1,2,5 (sunday is 0)), $which_week, $recurrence_ends
				if (($bits[3]=='d') || ($bits[3]=='l')) $bits[3]='1';
				$recurrence_part=str_repeat('0',intval($bits[3])-1).'1';
				switch ($bits[1])
				{
					case 'd':
						$recurrence='daily';
						if ($bits[4]!='') $recurrences=(strtotime($bits[4])-strtotime($row['event_start']))/(60*60*24*$bits[0]*(is_numeric($bits[3])?intval($bits[3]):1));
						break;
					case 'w':
						if ($bits[2]=='')
						{
							$recurrence='weekly';
						} else
						{
							$days=explode(',',$bits[2]);
							$string='0000000';
							foreach ($days as $day)
							{
								$string[$day]='1';
							}
							$recurrence='weekly';
							$recurrence_part=str_repeat('0000000',intval($bits[3])-1).$string;
						}
						if ($bits[4]!='') $recurrences=(strtotime($bits[4])-strtotime($row['event_start']))/(60*60*24*7*$bits[0]*(is_numeric($bits[3])?intval($bits[3]):1));
						break;
					case 'm':
						$recurrence='monthly';
						if ($bits[4]!='') $recurrences=(strtotime($bits[4])-strtotime($row['event_start']))/(60*60*24*32*$bits[0]*(is_numeric($bits[3])?intval($bits[3]):1));
						break;
					case 'y':
						$recurrence='yearly';
						if ($bits[4]!='') $recurrences=(strtotime($bits[4])-strtotime($row['event_start']))/(60*60*24*365*$bits[0]*(is_numeric($bits[3])?intval($bits[3]):1));
						break;
				}

				$string=str_pad('',strlen($recurrence_part)*intval($bits[0]));
				for ($i=0;$i<strlen($string);$i++)
				{
					$string[$i]=$recurrence_part[$i/intval($bits[0])];
				}
				$recurrence.=' '.$string;
			}
			list($start_year,$start_month,$start_day,$start_hour,$start_minute)=explode('-',date('Y-m-d-h-i',strtotime($row['event_start'])));
			list($end_year,$end_month,$end_day,$end_hour,$end_minute)=explode('-',date('Y-m-d-h-i',strtotime($row['event_end'])));
			$id_new=add_calendar_event(db_get_first_id()+1,$recurrence,intval(floor($recurrences)),0,$row['event_title'],$row['event_note'],3,$row['event_public'],$start_year,$start_month,$start_day,$start_hour,$start_minute,$end_year,$end_month,$end_day,$end_hour,$end_minute,NULL,1,$submitter,0,strtotime($row['event_start']));

			import_id_remap_put('event',strval($row['event_id']),$id_new);
		}
	}

}


