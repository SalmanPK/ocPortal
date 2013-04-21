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
 * @package		news
 */

/*
RSS IMPORT (works very well with Wordpress and Blogger, which use RSS as an interchange)
*/

/**
 * Get UI fields for starting news import.
 *
 * @param  boolean	Whether to import to blogs, by default
 * @return tempcode	UI fields
 */
function import_rss_fields($import_to_blog)
{
	$fields=new ocp_tempcode();

	$set_name='rss';
	$required=true;
	$set_title=do_lang_tempcode('FILE');
	$field_set=alternate_fields_set__start($set_name);

	$field_set->attach(form_input_upload(do_lang_tempcode('UPLOAD'),'','file_novalidate',false,NULL,NULL,true,'rss,xml,atom'));
	$field_set->attach(form_input_line(do_lang_tempcode('URL'),'','rss_feed_url','',false));

	$fields->attach(alternate_fields_set__end($set_name,$set_title,do_lang_tempcode('DESCRIPTION_WP_XML'),$field_set,$required));

	$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>false,'TITLE'=>do_lang_tempcode('ADVANCED'))));

	$fields->attach(form_input_tick(do_lang_tempcode('IMPORT_BLOG_COMMENTS'),do_lang_tempcode('DESCRIPTION_IMPORT_BLOG_COMMENTS'),'import_blog_comments',true));
	if (addon_installed('unvalidated'))
		$fields->attach(form_input_tick(do_lang_tempcode('AUTO_VALIDATE_ALL_POSTS'),do_lang_tempcode('DESCRIPTION_VALIDATE_ALL_POSTS'),'auto_validate',true));
	if ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()))
		$fields->attach(form_input_tick(do_lang_tempcode('ADD_TO_OWN_ACCOUNT'),do_lang_tempcode('DESCRIPTION_ADD_TO_OWN_ACCOUNT'),'add_to_own',false));
	if (has_specific_permission(get_member(),'draw_to_server'))
		$fields->attach(form_input_tick(do_lang_tempcode('DOWNLOAD_IMAGES'),do_lang_tempcode('DESCRIPTION_DOWNLOAD_IMAGES'),'download_images',true));
	if ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()))
		$fields->attach(form_input_tick(do_lang_tempcode('IMPORT_TO_BLOG'),do_lang_tempcode('DESCRIPTION_IMPORT_TO_BLOG'),'import_to_blog',true));

	return $fields;
}

/**
 * Import RSS
 */
function import_rss()
{
	require_code('rss');
	require_code('files');

	$GLOBALS['LAX_COMCODE']=true;

	disable_php_memory_limit();
	if (function_exists('set_time_limit')) @set_time_limit(0);

	$is_validated=post_param_integer('auto_validate',0);
	if (!addon_installed('unvalidated')) $is_validated=1;
	$download_images=post_param_integer('download_images',0);
	if (!has_specific_permission(get_member(),'draw_to_server')) $download_images=0;
	$to_own_account=post_param_integer('add_to_own',0);
	if (!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) $to_own_account=0;
	$import_blog_comments=post_param_integer('import_blog_comments',0);
	$import_to_blog=post_param_integer('import_to_blog',0);
	if (!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) $import_to_blog=0;

	$rss_url=post_param('rss_feed_url',NULL);
	require_code('uploads');
	if (((is_swf_upload(true)) && (array_key_exists('file_novalidate',$_FILES))) || ((array_key_exists('file_novalidate',$_FILES)) && (is_uploaded_file($_FILES['file_novalidate']['tmp_name']))))
	{
		$rss_url=$_FILES['file_novalidate']['tmp_name'];
	}
	if (is_null($rss_url))
		warn_exit(do_lang_tempcode('IMPROPERLY_FILLED_IN'));

	$rss=new rss($rss_url,true);

	if (!is_null($rss->error))
	{
		warn_exit($rss->error);
	}

	$imported_news=array();
	$imported_pages=array();

	// Preload news categories
	$NEWS_CATS=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('nc_owner'=>NULL));
	$NEWS_CATS=list_to_map('id',$NEWS_CATS);
	foreach ($rss->gleamed_items as $i=>$item)
	{
		// What is it, being imported?
		$is_news=true;
		if ((isset($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:POST_TYPE'])) && ($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:POST_TYPE']=='page'))
			$is_news=false;

		// Check for existing owner categories, if not create blog category for creator
		if (($to_own_account==0) && (array_key_exists('author',$item)))
		{
			$creator=$item['author'];
			$submitter_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($creator);
			if (is_null($submitter_id)) $submitter_id=get_member();
		} else
		{
			$submitter_id=get_member();
		}
		$author=array_key_exists('author',$item)?$item['author']:$GLOBALS['FORUM_DRIVER']->get_username(get_member());

		// Post name
		$post_name=$item['title'];
		if ((isset($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:POST_NAME'])) && ($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:POST_NAME']!=''))
		{
			$post_name=$item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:POST_NAME'];
		}

		// Dates
		$add_date=array_key_exists('clean_add_date',$item)?$item['clean_add_date']:(array_key_exists('add_date',$item)?strtotime($item['add_date']):time());
		if ($add_date===false) $add_date=time(); // We've seen this situation in an error email, it's if the add date won't parse by PHP
		$edit_date=array_key_exists('clean_edit_date',$item)?$item['clean_edit_date']:(array_key_exists('edit_date',$item)?strtotime($item['edit_date']):NULL);
		if ($edit_date===false) $edit_date=NULL;
		if ($add_date>time()) $add_date=time();
		if ($add_date<0) $add_date=time();

		// Validation status
		$validated=$is_validated;
		if (isset($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:STATUS']))
		{
			if ($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:STATUS']=='publish')
				$validated=1;
			else
				$validated=0;
			if (!addon_installed('unvalidated')) $validated=1;
		}

		// Whether to allow comments
		$allow_comments=1;
		if (isset($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:COMMENT_STATUS']))
		{
			if ($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:COMMENT_STATUS']=='open')
				$allow_comments=1;
			else
				$allow_comments=0;
		}

		// Whether to allow trackbacks
		$allow_trackbacks=$is_news?1:0;
		if (isset($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:PING_STATUS']))
		{
			if ($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:PING_STATUS']!='open') $allow_trackbacks=0;
		}

		// Password
		$password='';
		if (isset($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:POST_PASSWORD']))
		{
			$password=$item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:POST_PASSWORD'];
		}

		// Categories
		if (!array_key_exists('category',$item)) $item['category']=do_lang('NC_general');
		$cats_to_process=array($item['category']);
		if (array_key_exists('extra_categories',$item))
			$cats_to_process=array_merge($cats_to_process,$item['extra_categories']);

		// Now import, whatever this is
		if ($is_news)
		{
			// Work out categories
			$owner_category_id=mixed();
			$cat_ids=array();
			foreach ($cats_to_process as $j=>$cat)
			{
				if ($cat=='Uncategorized') continue; // Skip blank category creation

				$cat_id=mixed();
				foreach ($NEWS_CATS as $_cat=>$news_cat)
				{				
					if (get_translated_text($news_cat['nc_title'])==$cat)
					{
						$cat_id=$_cat;					
					}
				}
				if (is_null($cat_id)) // Could not find existing category, create new
				{
					$cat_id=add_news_category($cat,'newscats/general','',NULL);
					// Need to reload now
					$NEWS_CATS=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('nc_owner'=>NULL));
					$NEWS_CATS=list_to_map('id',$NEWS_CATS);
				}

				if (($j==0) && ($import_to_blog==1))
				{
					$owner_category_id=$cat_id; // Primary
				} else
				{
					$cat_ids[]=$cat_id; // Secondary
				}
			}
			if (is_null($owner_category_id))
			{
				$owner_category_id=$GLOBALS['SITE_DB']->query_value_null_ok('news_categories','id',array('nc_owner'=>$submitter_id));
			}

			// Work out rep-image
			$rep_image='';
			if (array_key_exists('rep_image',$item))
			{
				$rep_image=$item['rep_image'];
				if ($download_images==1)
				{
					$stem='uploads/grepimages/'.basename(urldecode($rep_image));
					$target_path=get_custom_file_base().'/'.$stem;
					$rep_image='uploads/grepimages/'.basename($rep_image);
					while (file_exists($target_path))
					{
						$uniqid=uniqid('');
						$stem='uploads/grepimages/'.$uniqid.'_'.basename(urldecode($rep_image));
						$target_path=get_custom_file_base().'/'.$stem;
						$rep_image='uploads/grepimages/'.$uniqid.'_'.basename($rep_image);
					}
					$target_handle=fopen($target_path,'wb') OR intelligent_write_error($target_path);
					$result=http_download_file($item['rep_image'],NULL,false,false,'ocPortal',NULL,NULL,NULL,NULL,NULL,$target_handle);
					fclose($target_handle);
				}
			}

			// Content
			$news=array_key_exists('news',$item)?import_foreign_news_html($item['news']):'';
			$news_article=array_key_exists('news_article',$item)?import_foreign_news_html($item['news_article']):'';
			if ($password!='')
			{
				$news_article='[highlight]'.do_lang('POST_ACCESS_IS_RESTRICTED').'[/highlight]'."\n\n".'[if_in_group="Administrators"]'.$news_article.'[/if_in_group]';
			}

			// Add news
			$id=add_news(
				$item['title'],
				$news,
				$author,
				$validated,
				1,
				$allow_comments,
				$allow_trackbacks,
				'',
				$news_article,
				$owner_category_id,
				$cat_ids,
				$add_date,
				$submitter_id,
				0,
				$edit_date,
				NULL,
				$rep_image
			);
			require_code('seo2');
			seo_meta_set_for_explicit('news',strval($id),implode(',',$cats_to_process),$news);

			// Track import IDs
			$rss->gleamed_items[$i]['import_id']=$id;
			$rss->gleamed_items[$i]['import__news']=$news;
			$rss->gleamed_items[$i]['import__news_article']=$news_article;
			$imported_news[]=$rss->gleamed_items[$i];

			// Needed for adding comments/trackbacks
			$comment_identifier='news_'.strval($id);
			$content_url=build_url(array('page'=>'news','type'=>'view','id'=>$id),get_module_zone('news'),NULL,false,false,true);
			$content_title=$item['title'];
			$trackback_for_type='news';
			$trackback_id=$id;
		} else
		{
			// If we don't have permission to write comcode pages, skip the page
			if (!has_submit_permission('high',get_member(),get_ip_address(),NULL,NULL)) continue;

			// Save articles as new comcode pages
			$zone='site';
			$lang=fallback_lang();
			$file=preg_replace('#[^\w\-]#','_',$post_name); // Filter non alphanumeric charactors
			$fullpath=zone_black_magic_filterer(get_custom_file_base().'/'.$zone.'/pages/comcode_custom/'.$lang.'/'.$file.'.txt');

			// Content
			$_content="[title]".comcode_escape($item['title'])."[/title]\n\n";
			$_content.='[surround]'.import_foreign_news_html(array_key_exists('news_article',$item)?$item['news_article']:$item['news']).'[/surround]';
			$_content.="\n\n[block]main_comcode_page_children[/block]";
			if ($allow_comments==1)
			{
				$_content.="\n\n[block=\"main\"]main_comments[/block]";
			}
			if ($allow_trackbacks==1)
			{
				$_content.="\n\n[block id=\"0\"]main_trackback[/block]";
			}

			// Add to the database
			$GLOBALS['SITE_DB']->query_delete('comcode_pages',array(
				'the_zone'=>$zone,
				'the_page'=>$file,
			),'',1);
			$GLOBALS['SITE_DB']->query_insert('comcode_pages',array(
				'the_zone'=>$zone,
				'the_page'=>$file,
				'p_parent_page'=>'',
				'p_validated'=>$validated,
				'p_edit_date'=>$edit_date,
				'p_add_date'=>$add_date,
				'p_submitter'=>$submitter_id,
				'p_show_as_edit'=>0
			));

			// Save to disk
			$myfile=@fopen($fullpath,'wt');
			if ($myfile===false) intelligent_write_error($fullpath);
			if (fwrite($myfile,$_content)<strlen($_content)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
			fclose($myfile);
			sync_file($fullpath);

			// Meta
			require_code('seo2');
			seo_meta_set_for_explicit('comcode_page',$zone.':'.$file,implode(',',$cats_to_process),'');

			// Track import IDs etc
			$parent_page=mixed();
			if (isset($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:POST_PARENT']))
			{
				$parent_page=intval($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:POST_PARENT']);
				if ($parent_page==0) $parent_page=NULL;
			}
			$page_id=mixed();
			if (isset($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:POST_ID']))
			{
				$page_id=intval($item['extra']['HTTP://WORDPRESS.ORG/EXPORT/1.2/:POST_ID']);
			}
			$imported_pages[]=array(
				'contents'=>$_content,
				'zone'=>$zone,
				'page'=>$file,
				'path'=>$fullpath,
				'parent_page'=>$parent_page,
				'id'=>$page_id,
			);

			// Restricted access
			if ($password!='')
			{
				$usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list();
				foreach (array_keys($usergroups) as $group_id)
				{
					$GLOBALS['SITE_DB']->query_delete('group_page_access',array('page_name'=>$file,'zone_name'=>$zone,'group_id'=>$group_id),'',1);
					$GLOBALS['SITE_DB']->query_insert('group_page_access',array('page_name'=>$file,'zone_name'=>$zone,'group_id'=>$group_id));
				}
			}

			// Needed for adding comments/trackbacks
			$comment_identifier=$file.'_main';
			$content_url=build_url(array('page'=>$file),$zone,NULL,false,false,true);
			$content_title=$item['title'];
			$trackback_for_type=$file;
			$trackback_id=0;
		}

		// Add comments
		if ($import_blog_comments==1)
		{
			if (array_key_exists('comments',$item))
			{
				$comment_mapping=array();
				foreach ($item['comments'] as $comment)
				{
					if (!array_key_exists('COMMENT_CONTENT',$comment)) continue;

					$comment_content=import_foreign_news_html($comment['COMMENT_CONTENT']);
					$comment_author=array_key_exists('COMMENT_AUTHOR',$comment)?$comment['COMMENT_AUTHOR']:do_lang('GUEST');
					$comment_parent=array_key_exists('COMMENT_PARENT',$comment)?$comment['COMMENT_PARENT']:'';
					$comment_date_gmt=array_key_exists('COMMENT_DATE_GMT',$comment)?$comment['COMMENT_DATE_GMT']:date('D/m/Y H:i:s',time());
					$author_ip=array_key_exists('COMMENT_AUTHOR_IP',$comment)?$comment['COMMENT_AUTHOR_IP']:get_ip_address();
					$comment_approved=array_key_exists('COMMENT_APPROVED',$comment)?$comment['COMMENT_APPROVED']:'1';
					$comment_id=array_key_exists('COMMENT_ID',$comment)?$comment['COMMENT_ID']:'';
					$comment_type=array_key_exists('COMMENT_TYPE',$comment)?$comment['COMMENT_TYPE']:'';

					$comment_author_url=array_key_exists('COMMENT_AUTHOR_URL',$comment)?$comment['COMMENT_AUTHOR_URL']:TODO;
					$comment_author_email=array_key_exists('COMMENT_AUTHOR_EMAIL',$comment)?$comment['COMMENT_AUTHOR_EMAIL']:TODO;

					$comment_add_date=strtotime($comment_date_gmt);
					if ($comment_add_date>time()) $comment_add_date=time();
					if ($comment_add_date<0) $comment_add_date=time();

					if (($comment_type=='trackback') || ($comment_type=='pingback'))
					{
						$GLOBALS['SITE_DB']->query_insert('trackbacks',array(
							'trackback_for_type'=>$trackback_for_type,
							'trackback_for_id'=>strval($trackback_id),
							'trackback_ip'=>$author_ip,
							'trackback_time'=>$comment_add_date,
							'trackback_url'=>$comment_author_url,
							'trackback_title'=>'',
							'trackback_excerpt'=>$comment_content,
							'trackback_name'=>$comment_author,
						));
						continue;
					}

					if ($comment_author_url!='')
						$comment_content.="\n\n".do_lang('WEBSITE').': [url]'.$comment_author_url.'[/url]';
					if ($comment_author_email!='')
						$comment_content.="[staff_note]\n\n".do_lang('EMAIL').': [email]'.$comment_author_email."[/email][/staff_note]";

					$submitter=$GLOBALS['FORUM_DB']->query_value_null_ok('f_members','id',array('m_username'=>$comment_author));
					if (is_null($submitter)) $submitter=$GLOBALS['FORUM_DRIVER']->get_guest_id(); // If comment is made by a non-member, assign comment to guest account

					$forum=(is_null(get_value('comment_forum__news')))?get_option('comments_forum_name'):get_value('comment_forum__news');

					$comment_parent_id=mixed();
					if ((get_forum_type()=='ocf') && (!is_null($comment_parent)) && (isset($comment_mapping[$comment_parent])))
					{
						$comment_parent_id=$comment_mapping[$comment_parent];
					}
					if ($comment_parent_id==0) $comment_parent_id=NULL;

					$result=$GLOBALS['FORUM_DRIVER']->make_post_forum_topic(
						$forum,
						$comment_identifier,
						$submitter,
						'', // Would be post title
						$comment_content,
						$content_title,
						do_lang('COMMENT'),
						$content_url->evaluate(),
						$comment_add_date,
						$author_ip,
						intval($comment_approved),
						1,
						false,
						$comment_author,
						$comment_parent_id
					);

					if (get_forum_type()=='ocf')
					{
						$comment_mapping[$comment_id]=$GLOBALS['LAST_POST_ID'];
					}
				}
			}
		}
	}

	// Download images etc
	foreach ($imported_news as $item)
	{
		$news=$item['import__news'];
		$news_article=$item['import__news_article'];
		_news_import_grab_images_and_fix_links($download_images==1,$news,$imported_news);
		_news_import_grab_images_and_fix_links($download_images==1,$news_article,$imported_news);
		lang_remap_comcode($GLOBALS['SITE_DB']->query_value('news','news',array('id'=>$item['import_id'])),$news);
		lang_remap_comcode($GLOBALS['SITE_DB']->query_value('news','news_article',array('id'=>$item['import_id'])),$news_article);
	}
	foreach ($imported_pages as $item)
	{
		$contents=$item['contents'];
		$zone=$item['zone'];
		$page=$item['page'];
		_news_import_grab_images_and_fix_links($download_images==1,$contents,$imported_news);
		$myfile=fopen($item['path'],'wb');
		fwrite($myfile,$contents);
		fclose($myfile);
		if (!is_null($item['parent_page']))
		{
			$parent_page=mixed();
			foreach ($imported_pages as $item2)
			{
				if ($item2['id']==$item['parent_page']) $parent_page=$item2['page'];
			}
			if (!is_null($parent_page))
			{
				$GLOBALS['SITE_DB']->query_update('comcode_pages',array('p_parent_page'=>$parent_page),array('the_zone'=>$zone,'the_page'=>$page),'',1);
			}
		}
	}

	// Cleanup
	if (url_is_local($rss_url)) // Means it is a temp file
		@unlink($rss_url);
}

/*
DIRECT WORDPRESS DATABASE IMPORT (imports more than RSS import can)
*/

/**
 * Import wordpress DB
 */
function import_wordpress_db()
{
	disable_php_memory_limit();
	if (function_exists('set_time_limit')) @set_time_limit(0);

	$GLOBALS['LAX_COMCODE']=true;

	$data=_get_wordpress_db_data();
	$is_validated=post_param_integer('wp_auto_validate',0);
	if (!addon_installed('unvalidated')) $is_validated=1;
	$download_images=post_param_integer('wp_download_images',0);
	if (!has_specific_permission(get_member(),'draw_to_server')) $download_images=0;
	$to_own_account=post_param_integer('wp_add_to_own',0);
	if (!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) $to_own_account=0;
	$import_blog_comments=post_param_integer('wp_import_blog_comments',0);
	$import_to_blog=post_param_integer('wp_import_to_blog',0);
	if (!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) $import_to_blog=0;

	if (get_forum_type()=='ocf')
	{
		require_code('ocf_members_action');
		require_code('ocf_groups');

		$def_grp_id=get_first_default_group();
	}

	$cat_id=array();

	$NEWS_CATS=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('nc_owner'=>NULL));
	$NEWS_CATS=list_to_map('id',$NEWS_CATS);

	$imported_news=array();
	$imported_pages=array();

	foreach ($data as $values)
	{
		// Lookup/import member
		if ($to_own_account==1)
		{
			// If post should go to own account
			$submitter_id=get_member();
		} else
		{
			if (get_forum_type()=='ocf')
			{
				$submitter_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_members','id',array('m_username'=>$values['user_login']));

				if (is_null($submitter_id))
				{
					if (post_param_integer('wp_import_wordpress_users',0)==1)
					{
						$submitter_id=ocf_make_member($values['user_login'],$values['user_pass'],'',NULL,NULL,NULL,NULL,array(),NULL,$def_grp_id,1,time(),time(),'',NULL,'',0,0,1,'','','',1,0,'',1,1,'',NULL,'',false,'wordpress');
					} else
					{
						$submitter_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username('admin');	// Set admin as owner
						if (is_null($submitter_id)) $submitter_id=$GLOBALS['FORUM_DRIVER']->get_guest_id()+1;
					}
				}
			} else
			{
				$submitter_id=$GLOBALS['FORUM_DRIVER']->get_guest_id(); // Guest user
			}
		}

		if (array_key_exists('POSTS',$values))
		{
			// Create news and pages
			foreach ($values['POSTS'] as $post_id=>$post)
			{
				// Validation status
				$validated=$is_validated;
				if ($post['post_status']=='publish')
					$validated=1;
				else
					$validated=0;
				if (!addon_installed('unvalidated')) $validated=1;

				// Allow comments/trackbacks
				$allow_comments=($post['comment_status']=='open')?1:0;
				$allow_trackbacks=($post['ping_status']=='open')?1:0;

				if ($post['post_type']=='post') // News
				{
					// Work out categories
					$owner_category_id=mixed();
					$cat_ids=array();
					if (array_key_exists('category',$post))
					{
						$i=0;
						foreach ($post['category'] as $category)
						{
							if ($category=='Uncategorized') continue;	// Skip blank category creation

							$cat_id=mixed();
							foreach ($NEWS_CATS as $id=>$existing_cat)
							{
								if (get_translated_text($existing_cat['nc_title'])==$category)
								{
									$cat_id=$id;
								}
							}
							if (is_null($cat_id)) // Could not find existing category, create new
							{
								$cat_id=add_news_category($category,'newscats/community',$category);
								// Need to reload now
								$NEWS_CATS=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'));
								$NEWS_CATS=list_to_map('id',$NEWS_CATS);
							}
							if (($i==0) && ($import_to_blog==1))
							{
								$owner_category_id=$cat_id; // Primary
							} else
							{
								$cat_ids[]=$cat_id; // Secondary
							}
							$i++;
						}
					}
					if (is_null($owner_category_id))
					{
						$owner_category_id=$GLOBALS['SITE_DB']->query_value_null_ok('news_categories','id',array('nc_owner'=>$submitter_id));
					}

					// Content
					$news='';
					$news_article=import_foreign_news_html($post['post_content']);
					if ($post['post_password']!='')
					{
						$news_article='[highlight]'.do_lang('POST_ACCESS_IS_RESTRICTED').'[/highlight]'."\n\n".'[if_in_group="Administrators"]'.$news_article.'[/if_in_group]';
					}

					// Add news
					$id=add_news(
						$post['post_title'],
						$news,
						NULL,
						$validated,
						1,
						$allow_comments,
						$allow_trackbacks,
						'',
						$news_article,
						$owner_category_id,
						$cat_ids,
						strtotime($post['post_date_gmt']),
						$submitter_id,
						0,
						is_null($post['post_modified_gmt'])?NULL:strtotime($post['post_modified_gmt']),
						NULL,
						''
					);
					if (array_key_exists('category',$post))
					{
						require_code('seo2');
						seo_meta_set_for_explicit('news',strval($id),implode(',',$post['category']),$news);
					}

					// Needed for adding comments/trackbacks
					$comment_identifier='news_'.strval($id);
					$content_url=build_url(array('page'=>'news','type'=>'view','id'=>$id),get_module_zone('news'),NULL,false,false,true);
					$content_title=$post['post_title'];
					$trackback_for_type='news';
					$trackback_id=$id;

					// Track import IDs
					$imported_news[]=array(
						//'full_url'=>'', We don't know this for a database import
						'import_id'=>$id,
						'import__news'=>$news,
						'import__news_article'=>$news_article,
					);
				}
				elseif ($post['post_type']=='page') // Page/articles
				{
					// If we don't have permission to write comcode pages, skip the page
					if (!has_submit_permission('high',get_member(),get_ip_address(),NULL,NULL)) continue;

					// Save articles as new comcode pages
					$zone='site';
					$lang=fallback_lang();
					$file=preg_replace('#[^\w\-]#','_',$post['post_name']); // Filter non alphanumeric charactors
					$fullpath=zone_black_magic_filterer(get_custom_file_base().'/'.$zone.'/pages/comcode_custom/'.$lang.'/'.$file.'.txt');

					// Content
					$_content="[title]".comcode_escape($post['post_title'])."[/title]\n\n";
					$_content.='[surround]'.import_foreign_news_html($post['post_content']).'[/surround]';
					$_content.="\n\n[block]main_comcode_page_children[/block]";
					if ($allow_comments==1)
					{
						$_content.="\n\n[block=\"main\"]main_comments[/block]";
					}
					if ($allow_trackbacks==1)
					{
						$_content.="\n\n[block id=\"0\"]main_trackback[/block]";
					}

					// Add to the database
					$GLOBALS['SITE_DB']->query_delete('comcode_pages',array(
						'the_zone'=>$zone,
						'the_page'=>$file,
					),'',1);
					$GLOBALS['SITE_DB']->query_insert('comcode_pages',array(
						'the_zone'=>$zone,
						'the_page'=>$file,
						'p_parent_page'=>'',
						'p_validated'=>$is_validated,
						'p_edit_date'=>strtotime($post['post_modified_gmt']),
						'p_add_date'=>strtotime($post['post_date_gmt']),
						'p_submitter'=>$submitter_id,
						'p_show_as_edit'=>0
					));

					// Save to disk
					$myfile=@fopen($fullpath,'wt');
					if ($myfile===false) intelligent_write_error($fullpath);
					if (fwrite($myfile,$_content)<strlen($_content)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
					fclose($myfile);
					sync_file($fullpath);

					// Meta
					if (array_key_exists('category',$post))
					{
						require_code('seo2');
						seo_meta_set_for_explicit('comcode_page',$zone.':'.$file,implode(',',$post['category']),'');
					}

					// Track import IDs etc
					$parent_page=$post['post_parent'];
					if ($parent_page==0) $parent_page=NULL;
					$imported_pages[]=array(
						'contents'=>$_content,
						'zone'=>$zone,
						'page'=>$file,
						'path'=>$fullpath,
						'parent_page'=>$parent_page,
						'id'=>$post['post_id'],
					);

					// Restricted access
					if ($post['post_password']!='')
					{
						$usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list();
						foreach (array_keys($usergroups) as $group_id)
						{
							$GLOBALS['SITE_DB']->query_delete('group_page_access',array('page_name'=>$file,'zone_name'=>$zone,'group_id'=>$group_id),'',1);
							$GLOBALS['SITE_DB']->query_insert('group_page_access',array('page_name'=>$file,'zone_name'=>$zone,'group_id'=>$group_id));
						}
					}

					// Needed for adding comments/trackbacks
					$comment_identifier=$file.'_main';
					$content_url=build_url(array('page'=>$file),$zone,NULL,false,false,true);
					$content_title=$post['post_title'];
					$trackback_for_type=$file;
					$trackback_id=0;
				}

				// Add comments
				if ($import_blog_comments==1)
				{
					if (array_key_exists('COMMENTS',$post))
					{
						$comment_mapping=array();
						foreach ($post['COMMENTS'] as $comment)
						{
							$submitter=$GLOBALS['FORUM_DB']->query_value_null_ok('f_members','id',array('m_username'=>$comment['comment_author']));
							if (is_null($submitter)) $submitter=$GLOBALS['FORUM_DRIVER']->get_guest_id(); // If comment is made by a non-member, assign comment to guest account

							$forum=(is_null(get_value('comment_forum__news')))?get_option('comments_forum_name'):get_value('comment_forum__news');

							$comment_parent_id=mixed();
							if ((get_forum_type()=='ocf') && (!is_null($comment['comment_parent'])) && (isset($comment_mapping[$comment['comment_parent']])))
							{
								$comment_parent_id=$comment_mapping[$comment['comment_parent']];
							}
							if ($comment_parent_id==0) $comment_parent_id=NULL;

							$comment_content=import_foreign_news_html($comment['comment_content']);

							$comment_author_url=$comment['comment_author_url'];
							$comment_author_email=$comment['comment_author_email'];

							$comment_type=$comment['comment_type'];
							if (($comment_type=='trackback') || ($comment_type=='pingback'))
							{
								$GLOBALS['SITE_DB']->query_insert('trackbacks',array(
									'trackback_for_type'=>$trackback_for_type,
									'trackback_for_id'=>strval($trackback_id),
									'trackback_ip'=>$comment['author_ip'],
									'trackback_time'=>strtotime($comment['comment_date_gmt']),
									'trackback_url'=>$comment_author_url,
									'trackback_title'=>'',
									'trackback_excerpt'=>$comment_content,
									'trackback_name'=>$comment['comment_author'],
								));
								continue;
							}

							if ($comment_author_url!='')
								$comment_content.="\n\n".do_lang('WEBSITE').': [url]'.$comment_author_url.'[/url]';
							if ($comment_author_email!='')
								$comment_content.="[staff_note]\n\n".do_lang('EMAIL').': [email]'.$comment_author_email."[/email][/staff_note]";

							$result=$GLOBALS['FORUM_DRIVER']->make_post_forum_topic(
								$forum,
								'news_'.strval($id),
								$submitter,
								'', // Would be post title
								$comment_content,
								$content_title,
								do_lang('COMMENT'),
								$content_url->evaluate(),
								strtotime($comment['comment_date_gmt']),
								$comment['author_ip'],
								$comment['comment_approved'],
								1,
								false,
								$comment['comment_author'],
								$comment_parent_id
							);

							if (get_forum_type()=='ocf')
							{
								$comment_mapping[$comment['comment_ID']]=$GLOBALS['LAST_POST_ID'];
							}
						}
					}
				}
			}
		}
	}

	// Download images etc
	foreach ($imported_news as $item)
	{
		$news=$item['import__news'];
		$news_article=$item['import__news_article'];
		_news_import_grab_images_and_fix_links($download_images==1,$news,$imported_news);
		_news_import_grab_images_and_fix_links($download_images==1,$news_article,$imported_news);
		lang_remap_comcode($GLOBALS['SITE_DB']->query_value('news','news',array('id'=>$item['import_id'])),$news);
		lang_remap_comcode($GLOBALS['SITE_DB']->query_value('news','news_article',array('id'=>$item['import_id'])),$news_article);
	}
	foreach ($imported_pages as $item)
	{
		$contents=$item['contents'];
		$zone=$item['zone'];
		$page=$item['page'];
		_news_import_grab_images_and_fix_links($download_images==1,$contents,$imported_news);
		$myfile=fopen($item['path'],'wb');
		fwrite($myfile,$contents);
		fclose($myfile);
		if (!is_null($item['parent_page']))
		{
			$parent_page=mixed();
			foreach ($imported_pages as $item2)
			{
				if ($item2['id']==$item['parent_page']) $parent_page=$item2['page'];
			}
			if (!is_null($parent_page))
			{
				$GLOBALS['SITE_DB']->query_update('comcode_pages',array('p_parent_page'=>$parent_page),array('the_zone'=>$zone,'the_page'=>$page),'',1);
			}
		}
	}
}

/**
 * Get data from the Wordpress DB
 *
 * @return array		Result structure
 */
function _get_wordpress_db_data()
{
	$host_name=post_param('wp_host');
	$db_name=post_param('wp_db');
	$db_user=post_param('wp_db_user');
	$db_passwrod=post_param('wp_db_password');
	$db_table_prefix=post_param('wp_table_prefix');

	// Create DB connection
	$db=new database_driver($db_name,$host_name,$db_user,$db_passwrod,$db_table_prefix);

	$users=$db->query('SELECT * FROM '.db_escape_string($db_name).'.'.db_escape_string($db_table_prefix).'_users');

	$data=array();
	foreach ($users as $user)
	{
		$user_id=$user['ID'];
		$data[$user_id]=$user;

		// Fetch user posts/pages
		$posts=$db->query('SELECT * FROM '.$db_table_prefix.'_posts WHERE post_author='.strval($user_id).' AND (post_type=\'post\' OR post_type=\'page\')');
		foreach ($posts as $post)
		{
			$post_id=$post['ID'];
			$data[$user_id]['POSTS'][$post_id]=$post;

			// Get categories
			$categories=$db->query('SELECT t1.slug,t1.name FROM '.$db_table_prefix.'_terms t1 JOIN '.db_escape_string($db_name).'.'.db_escape_string($db_table_prefix).'_term_taxonomy t2 ON t1.term_id=t2.term_id JOIN '.db_escape_string($db_name).'.'.db_escape_string($db_table_prefix).'_term_relationships t3 ON t2.term_taxonomy_id=t3.term_taxonomy_id WHERE t3.object_id='.strval($post_id).' ORDER BY t3.term_order');
			foreach ($categories as $category)
			{
				$data[$user_id]['POSTS'][$post_id]['category'][$category['slug']]=$category['name'];
			}

			// Comments
			$comments=$db->query('SELECT * FROM '.$db_table_prefix.'_comments WHERE comment_post_ID='.strval($post_id).' ORDER BY comment_date_gmt');
			foreach ($comments as $comment)
			{
				$comment_id=$comment['comment_ID'];
				$data[$user_id]['POSTS'][$post_id]['COMMENTS'][$comment_id]=$comment;
			}
		}
	}

	return $data;
}

/*
NEWS IMPORT UTILITY FUNCTIONS
*/

/**
 * Get data from wordpress DB.
 *
 * @param  string		HTML
 * @return string		Comcode
 */
function import_foreign_news_html($html)
{
	// Wordpress images
	$matches=array();
	$num_matches=preg_match_all('#\[caption id="(\w+)" align="align(left|right|center|none)" width="(\d+)"\](.*)\[/caption\]#Us',$html,$matches);
	for ($i=0;$i<$num_matches;$i++)
	{
		$test=strpos($matches[4][$i],' /></a> ');
		if ($test!==false)
		{
			$matches[4][$i]=substr($matches[4][$i],0,$test).' /></a> <p class="wp-caption-text">'.substr($matches[4][$i],$test+strlen(' /></a> ')).'</p>';
		} else
		{
			$test=strpos($matches[4][$i],' /> ');
			if ($test!==false)
			{
				$matches[4][$i]=substr($matches[4][$i],0,$test).' /> <p class="wp-caption-text">'.substr($matches[4][$i],$test+strlen(' /> ')).'</p>';
			}
		}
		$new='[surround="attachment wp-caption align'.$matches[2][$i].' '.$matches[1][$i].'" style="width: '.$matches[3][$i].'px"]'.$matches[4][$i].'[/surround]';
		$html=str_replace($matches[0][$i],$new,$html);
	}
	$html=preg_replace('#<a([^>]*)><img #','<a rel="lightbox"${1}><img ',$html);

	// Blogger images
	$html=str_replace('imageanchor="1"','rel="lightbox"',$html);

	// General conversion to Comcode
	require_code('comcode_from_html');
	return semihtml_to_comcode($html,false);
}

/**
 * Download remote images in some HTML and replace with local references under uploads/website_specific AND fix any links to other articles being imported to make them local links.
 *
 * @param  boolean		Whether to download images to local
 * @param  string			HTML (passed by reference)
 * @param  array			Imported items, in ocPortal's RSS-parsed format [list of maps containing full_url and import_id] (used to fix links)
 */
function _news_import_grab_images_and_fix_links($download_images,&$data,$imported_news)
{
	$matches=array();
	if ($download_images)
	{
		$num_matches=preg_match_all('#<img[^<>]*\ssrc=["\']([^\'"]*)["\']#i',$data,$matches); // If there's an <a> to the same URL, this will be replaced too
		for ($i=0;$i<$num_matches;$i++)
			_news_import_grab_image($data,$matches[1][$i]);
		$num_matches=preg_match_all('#<a[^<>]*\s*href=["\']([^\'"]*)["\']\s*imageanchor=["\']1["\']#i',$data,$matches);
		for ($i=0;$i<$num_matches;$i++)
			_news_import_grab_image($data,$matches[1][$i]);
		$num_matches=preg_match_all('#<a rel="lightbox" href=["\']([^\'"]*)["\']#i',$data,$matches);
		for ($i=0;$i<$num_matches;$i++)
			_news_import_grab_image($data,$matches[1][$i]);
	}

	// Go through other items, in case this news article/page is linking to them and needs a fixed link
	foreach ($imported_news as $item)
	{
		if (array_key_exists('full_url',$item))
		{
			$num_matches=preg_match_all('#<a\s*([^<>]*)href="'.str_replace('#','\#',preg_quote(escape_html($item['full_url']))).'"([^<>]*)>(.*)</a>#isU',$data,$matches);
			for ($i=0;$i<$num_matches;$i++)
			{
				if (($matches[1][$i]=='') && ($matches[2][$i]=='') && (strpos($data,'[html]')===false))
				{
					$data=str_replace($matches[0][$i],'[page="_SEARCH:news:view:'.strval($item['import_id']).'"]'.$matches[3][$i].'[/page]',$data);
				} else
				{
					$new_url=build_url(array('page'=>'news','type'=>'view','id'=>$item['import_id']),get_module_zone('news'),NULL,false,false,true);
					$data=str_replace($matches[0][$i],'<a '.$matches[1][$i].'href="'.escape_html($new_url->evaluate()).'"'.$matches[2][$i].'>'.$matches[3][$i].'</a>',$data);
				}
			}
		}
	}
}

/**
 * Download a specific remote image and sub in the new URL.
 *
 * @param  string			HTML (passed by reference)
 * @param  URLPATH		URL
 */
function _news_import_grab_image(&$data,$url)
{
	$url=qualify_url($url,get_base_url());
	if (substr($url,0,strlen(get_custom_base_url().'/'))==get_custom_base_url().'/') return;
	require_code('images');
	if (!is_image($url)) return;

	$stem='uploads/attachments/'.basename(urldecode($url));
	$target_path=get_custom_file_base().'/'.$stem;
	$target_url=get_custom_base_url().'/uploads/attachments/'.basename($url);
	while (file_exists($target_path))
	{
		$uniqid=uniqid('');
		$stem='uploads/attachments/'.$uniqid.'_'.basename(urldecode($url));
		$target_path=get_custom_file_base().'/'.$stem;
		$target_url=get_custom_base_url().'/uploads/attachments/'.$uniqid.'_'.basename($url);
	}

	$target_handle=fopen($target_path,'wb') OR intelligent_write_error($target_path);
	$result=http_download_file($url,NULL,false,false,'ocPortal',NULL,NULL,NULL,NULL,NULL,$target_handle);
	fclose($target_handle);
	if (!is_null($result))
	{
		$data=str_replace('"'.$url.'"',$target_url,$data);
		$data=str_replace('"'.preg_replace('#^http://.*/#U','/',$url).'"',$target_url,$data);
		$data=str_replace('\''.$url.'\'',$target_url,$data);
		$data=str_replace('\''.preg_replace('#^http://.*/#U','/',$url).'\'',$target_url,$data);
	}
}
