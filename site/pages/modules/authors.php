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
 * @package		authors
 */

/**
 * Module page class.
 */
class Module_authors
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
		$info['version']=3;
		$info['locked']=true;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('authors');

		delete_menu_item_simple('_SELF:authors:type=misc');
		delete_menu_item_simple('_SEARCH:cms_authors:type=_ad');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('authors',array(
				'author'=>'*ID_TEXT',
				'url'=>'URLPATH',
				'forum_handle'=>'?USER',
				'description'=>'LONG_TRANS',	// Comcode
				'skills'=>'LONG_TRANS'	// Comcode
			));

			$GLOBALS['SITE_DB']->create_index('authors','findmemberlink',array('forum_handle'));
		}
		if ((!is_null($upgrade_from)) && ($upgrade_from<3))
		{
			$GLOBALS['SITE_DB']->alter_table_field('authors','forum_handle','?USER');
		}

		// collab_features
		require_lang('authors');
		add_menu_item_simple('collab_features',NULL,'VIEW_MY_AUTHOR_PROFILE','_SELF:authors:type=misc');
		add_menu_item_simple('collab_features',NULL,'EDIT_MY_AUTHOR_PROFILE','_SEARCH:cms_authors:type=_ad',0,0,true,do_lang('ZONE_BETWEEN'),1);
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return is_guest()?array():array('misc'=>'VIEW_MY_AUTHOR_PROFILE');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		$GLOBALS['FEED_URL']=find_script('backend').'?mode=authors&filter=';

		require_code('authors');
		require_lang('authors');

		// Decide what we're doing
		$type=get_param('type','misc');

		if ($type=='misc') return $this->show_author();

		return new ocp_tempcode();
	}

	/**
	 * The UI to view an author.
	 *
	 * @return tempcode		The UI
	 */
	function show_author()
	{
		$author=get_param('id',NULL);
		if (is_null($author))
		{
			if (is_guest())
			{
				global $EXTRA_HEAD;
				$EXTRA_HEAD->attach('<meta name="robots" content="noindex" />'); // XHTMLXHTML

				warn_exit(do_lang_tempcode('USER_NO_EXIST'));
			}

			$author=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
		}
		if ((is_null($author)) || ($author=='')) warn_exit(do_lang_tempcode('INTERNAL_ERROR')); // Really don't want to have to search on this

		if (addon_installed('awards'))
		{
			require_code('awards');
			$awards=find_awards_for('author',$author);
		} else $awards=array();
		$title=get_screen_title('_AUTHOR',true,array(escape_html($author)),NULL,$awards);

		seo_meta_load_for('authors',$author);

		$rows=$GLOBALS['SITE_DB']->query_select('authors',array('url','description','skills'),array('author'=>$author),'',1);
		if (!array_key_exists(0,$rows))
		{
			if ((has_actual_page_access(get_member(),'cms_authors')) && (has_edit_author_permission(get_member(),$author)))
			{
				$GLOBALS['HTTP_STATUS_CODE']='404';
				if (!headers_sent())
				{
					if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 404 Not Found');
				}

				$_author_add_url=build_url(array('page'=>'cms_authors','type'=>'_ad','author'=>$author),get_module_zone('cms_authors'));
				$author_add_url=$_author_add_url->evaluate();
				$message=do_lang_tempcode('NO_SUCH_AUTHOR_CONFIGURE_ONE',escape_html($author),escape_html($author_add_url));

				attach_message($message,'inform');
			} else
			{
				$message=do_lang_tempcode('NO_SUCH_AUTHOR',escape_html($author));
			}
			$details=array('author'=>$author,'url'=>'','forum_handle'=>$GLOBALS['FORUM_DRIVER']->get_member_from_username($author),'description'=>NULL,'skills'=>NULL,);
			//return inform_screen($title,$message);
		} else
		{
			$details=$rows[0];
		}

		// Links associated with the mapping between the author and a forum member
		$handle=get_author_id_from_name($author);
		if (!is_null($handle))
		{
			$forum_details=do_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY',array('ACTION'=>hyperlink($GLOBALS['FORUM_DRIVER']->member_profile_url($handle,true,true),do_lang_tempcode('AUTHOR_PROFILE'),false,false,'',NULL,NULL,'me')));
			if (addon_installed('points'))
			{
				$give_points_url=build_url(array('page'=>'points','type'=>'member','id'=>$handle),get_module_zone('points'));
				$point_details=do_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY',array('ACTION'=>hyperlink($give_points_url,do_lang_tempcode('AUTHOR_POINTS'))));
			}
			else $point_details=new ocp_tempcode();
		} else
		{
			$forum_details=new ocp_tempcode();
			$point_details=new ocp_tempcode();
		}

		// Homepage
		$url=$details['url'];
		if (strlen($url)>0)
			$url_details=do_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY',array('ACTION'=>hyperlink($url,do_lang_tempcode('AUTHOR_HOMEPAGE'),false,false,'',NULL,NULL,'me')));
		else $url_details=new ocp_tempcode();

		// (Self?) description
		$description=is_null($details['description'])?new ocp_tempcode():get_translated_tempcode($details['description']);

		// Skills
		$skills=is_null($details['skills'])?new ocp_tempcode():get_translated_tempcode($details['skills']);

		// Edit link, for staff
		if (has_edit_author_permission(get_member(),$author))
		{
			$edit_author_url=build_url(array('page'=>'cms_authors','type'=>'_ad','author'=>$author),get_module_zone('cms_authors'));
			$staff_details=do_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY',array('ACTION'=>hyperlink($edit_author_url,do_lang_tempcode('DEFINE_AUTHOR'),false)));
		}
		else $staff_details=new ocp_tempcode();

		// Search link
		if (addon_installed('search'))
		{
			$search_url=build_url(array('page'=>'search','author'=>$author),get_module_zone('search'));
			$search_details=do_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY',array('ACTION'=>hyperlink($search_url,do_lang_tempcode('SEARCH'),false)));
		}
		else $search_details=new ocp_tempcode();

		// Downloads
		$downloads_released=new ocp_tempcode();
		if (addon_installed('downloads'))
		{
			require_code('downloads');
			require_lang('downloads');

			$count=$GLOBALS['SITE_DB']->query_value('download_downloads','COUNT(*)',array('author'=>$author,'validated'=>1));
			if ($count>50)
			{
				$downloads_released=paragraph(do_lang_tempcode('TOO_MANY_TO_CHOOSE_FROM'));
			} else
			{
				$rows=$GLOBALS['SITE_DB']->query_select('download_downloads',array('*'),array('author'=>$author,'validated'=>1));
				require_code('downloads');
				foreach ($rows as $myrow)
				{
					if (has_category_access(get_member(),'downloads',strval($myrow['category_id'])))
						$downloads_released->attach(render_download_box($myrow));
				}
			}
		}

		// News
		$news_released=new ocp_tempcode();
		if (addon_installed('news'))
		{
			require_lang('news');

			$count=$GLOBALS['SITE_DB']->query_value('news','COUNT(*)',array('author'=>$author,'validated'=>1));
			if ($count>50)
			{
				$news_released=paragraph(do_lang_tempcode('TOO_MANY_TO_CHOOSE_FROM'));
			} else
			{
				$rows=$GLOBALS['SITE_DB']->query_select('news',array('*'),array('author'=>$author,'validated'=>1));
				foreach ($rows as $i=>$row)
				{
					if (has_category_access(get_member(),'news',strval($row['news_category'])))
					{
						$url=build_url(array('page'=>'news','type'=>'view','id'=>$row['id']),get_module_zone('news'));
						$_title=get_translated_tempcode($row['title']);
						$title_plain=get_translated_text($row['title']);
						$seo_bits=seo_meta_get_for('news',strval($row['id']));
						$map=array('ID'=>strval($row['id']),'TAGS'=>get_loaded_tags('news',explode(',',$seo_bits[0])),'SUBMITTER'=>strval($row['submitter']),'DATE'=>get_timezoned_date($row['date_and_time']),'DATE_RAW'=>strval($row['date_and_time']),'URL'=>$url,'TITLE_PLAIN'=>$title_plain,'TITLE'=>$_title);
						if ((get_option('is_on_comments')=='1') && (!has_no_forum()) && ($row['allow_comments']>=1)) $map['COMMENT_COUNT']='1';
						$tpl=do_template('NEWS_BRIEF',$map);
						$news_released->attach($tpl);
					}
				}
			}
		}

		// Edit link
		$edit_url=new ocp_tempcode();
		if (has_edit_author_permission(get_member(),$author))
		{
			$edit_url=build_url(array('page'=>'cms_authors','type'=>'_ad','author'=>$author),get_module_zone('cms_authors'));
		}

		return do_template('AUTHOR_SCREEN',array('_GUID'=>'ea789367b15bc90fc28d1c586e6e6536','TAGS'=>get_loaded_tags(),'TITLE'=>$title,'EDIT_URL'=>$edit_url,'AUTHOR'=>$author,'NEWS_RELEASED'=>$news_released,'DOWNLOADS_RELEASED'=>$downloads_released,'STAFF_DETAILS'=>$staff_details,'POINT_DETAILS'=>$point_details,'SEARCH_DETAILS'=>$search_details,'URL_DETAILS'=>$url_details,'FORUM_DETAILS'=>$forum_details,'SKILLS'=>$skills,'DESCRIPTION'=>$description));
	}

}


