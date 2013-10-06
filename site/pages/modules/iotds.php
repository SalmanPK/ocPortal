<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		iotds
 */

/**
 * Module page class.
 */
class Module_iotds
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
		$info['version']=4;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('iotd');

		delete_privilege('choose_iotd');

		$GLOBALS['SITE_DB']->query_delete('trackbacks',array('trackback_for_type'=>'iotds'));

		require_code('files');
		deldir_contents(get_custom_file_base().'/uploads/iotds',true);
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
			$GLOBALS['SITE_DB']->create_table('iotd',array(
				'id'=>'*AUTO',
				'url'=>'URLPATH',
				'i_title'=>'SHORT_TRANS',
				'caption'=>'LONG_TRANS',	// Comcode
				'thumb_url'=>'URLPATH',
				'is_current'=>'BINARY',
				'allow_rating'=>'BINARY',
				'allow_comments'=>'SHORT_INTEGER',
				'allow_trackbacks'=>'BINARY',
				'notes'=>'LONG_TEXT',
				'used'=>'BINARY',
				'date_and_time'=>'?TIME',
				'iotd_views'=>'INTEGER',
				'submitter'=>'MEMBER',
				'add_date'=>'TIME',
				'edit_date'=>'?TIME'
			));

			$GLOBALS['SITE_DB']->create_index('iotd','iotd_views',array('iotd_views'));
			$GLOBALS['SITE_DB']->create_index('iotd','get_current',array('is_current'));
			$GLOBALS['SITE_DB']->create_index('iotd','ios',array('submitter'));
			$GLOBALS['SITE_DB']->create_index('iotd','iadd_date',array('add_date'));
			$GLOBALS['SITE_DB']->create_index('iotd','date_and_time',array('date_and_time'));

			add_privilege('IOTDS','choose_iotd',false);

			$GLOBALS['SITE_DB']->create_index('iotd','ftjoin_icap',array('caption'));
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'IOTDS');
	}

	/**
	 * Standard modular page-link finder function (does not return the main entry-points that are not inside the tree).
	 *
	 * @param  ?integer  The number of tree levels to computer (NULL: no limit)
	 * @param  boolean	Whether to not return stuff that does not support permissions (unless it is underneath something that does).
	 * @param  ?string	Position to start at in the tree. Does not need to be respected. (NULL: from root)
	 * @param  boolean	Whether to avoid returning categories.
	 * @return ?array	 	A tuple: 1) full tree structure [made up of (pagelink, permission-module, permissions-id, title, children, ?entry point for the children, ?children permission module, ?whether there are children) OR a list of maps from a get_* function] 2) permissions-page 3) optional base entry-point for the tree 4) optional permission-module 5) optional permissions-id (NULL: disabled).
	 */
	function get_page_links($max_depth=NULL,$require_permission_support=false,$start_at=NULL,$dont_care_about_categories=false)
	{
		$permission_page='cms_iotds';

		return array(array(),$permission_page);
	}

	/**
	 * Standard modular new-style deep page-link finder function (does not return the main entry-points).
	 *
	 * @param  string  	Callback function to send discovered page-links to.
	 * @param  MEMBER		The member we are finding stuff for (we only find what the member can view).
	 * @param  integer	Code for how deep we are tunnelling down, in terms of whether we are getting entries as well as categories.
	 * @param  string		Stub used to create page-links. This is passed in because we don't want to assume a zone or page name within this function.
	 */
	function get_sitemap_pagelinks($callback,$member_id,$depth,$pagelink_stub)
	{
		// Entries
		if ($depth>=DEPTH__ENTRIES)
		{
			$rows=$GLOBALS['SITE_DB']->query_select('iotd c LEFT JOIN '.get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND t.id=c.i_title',array('c.i_title','c.id','t.text_original AS title','add_date','edit_date'),array('used'=>1));

			foreach ($rows as $row)
			{
				if (is_null($row['title'])) $row['title']=get_translated_text($row['i_title']);
				$pagelink=$pagelink_stub.'view:'.strval($row['id']);
				call_user_func_array($callback,array($pagelink,$pagelink_stub.'misc',$row['add_date'],$row['edit_date'],0.2,$row['title'])); // Callback
			}
		}
	}

	var $title;
	var $id;
	var $myrow;
	var $url;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		if ($type=='misc')
		{
			$this->title=get_screen_title('IOTD_ARCHIVE');
		}

		if ($type=='view')
		{
			set_feed_url('?mode=iotds&filter=');

			$id=get_param_integer('id');

			require_lang('iotds');

			// Breadcrumbs
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('IOTD_ARCHIVE'))));

			// Fetch details
			$rows=$GLOBALS['SITE_DB']->query_select('iotd',array('*'),array('id'=>$id),'',1);
			if (!array_key_exists(0,$rows))
			{
				return warn_screen($this->title,do_lang_tempcode('MISSING_RESOURCE'));
			}
			$myrow=$rows[0];
			$url=$myrow['url'];
			if (url_is_local($url)) $url=get_custom_base_url().'/'.$url;

			// Meta data
			set_extra_request_metadata(array(
				'created'=>date('Y-m-d',$myrow['add_date']),
				'creator'=>$GLOBALS['FORUM_DRIVER']->get_username($myrow['submitter']),
				'publisher'=>'', // blank means same as creator
				'modified'=>is_null($myrow['edit_date'])?'':date('Y-m-d',$myrow['edit_date']),
				'type'=>'Poll',
				'title'=>get_translated_text($myrow['i_title']),
				'identifier'=>'_SEARCH:iotds:view:'.strval($id),
				'description'=>'',
				'image'=>$url,
			));

			$this->title=get_screen_title('IOTD');

			$id=$this->id;
			$myrow=$this->myrow;
			$url=$this->url;
		}

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_code('feedback');
		require_code('iotds');
		require_css('iotds');

		// What action are we going to do?
		$type=get_param('type','misc');

		if ($type=='misc') return $this->iotd_browse();
		if ($type=='view') return $this->view();

		return new ocp_tempcode();
	}

	/**
	 * The UI to browse IOTDs.
	 *
	 * @return tempcode		The UI
	 */
	function iotd_browse()
	{
		$content=do_block('main_multi_content',array('param'=>'iotd','efficient'=>'0','zone'=>'_SELF','sort'=>'recent','max'=>'10','no_links'=>'1','pagination'=>'1','give_context'=>'0','include_breadcrumbs'=>'0','block_id'=>'module'));

		return do_template('PAGINATION_SCREEN',array('_GUID'=>'d8a493c2b007d98074f104ea433c8091','TITLE'=>$this->title,'CONTENT'=>$content));
	}

	/**
	 * The UI to view an IOTD.
	 *
	 * @return tempcode		The UI
	 */
	function view()
	{
		$id=$this->id;
		$myrow=$this->myrow;
		$url=$this->url;

		// Feedback
		list($rating_details,$comment_details,$trackback_details)=embed_feedback_systems(
			get_page_name(),
			strval($id),
			$myrow['allow_rating'],
			$myrow['allow_comments'],
			$myrow['allow_trackbacks'],
			((is_null($myrow['date_and_time'])) && ($myrow['used']==0))?0:1,
			$myrow['submitter'],
			build_url(array('page'=>'_SELF','type'=>'view','id'=>$id),'_SELF',NULL,false,false,true),
			get_translated_text($myrow['i_title']),
			find_overridden_comment_forum('iotds'),
			$myrow['add_date']
		);

		// Views
		if ((get_db_type()!='xml') && (get_value('no_view_counts')!=='1'))
		{
			$myrow['iotd_views']++;
			if (!$GLOBALS['SITE_DB']->table_is_locked('iotd'))
				$GLOBALS['SITE_DB']->query_update('iotd',array('iotd_views'=>$myrow['iotd_views']),array('id'=>$id),'',1,NULL,false,true);
		}

		// Management links
		if ((has_actual_page_access(NULL,'cms_iotds',NULL,NULL)) && (has_edit_permission('high',get_member(),$myrow['submitter'],'cms_iotds')))
		{
			$edit_url=build_url(array('page'=>'cms_iotds','type'=>'_ed','id'=>$id),get_module_zone('cms_iotds'));
		} else $edit_url=new ocp_tempcode();

		return do_template('IOTD_ENTRY_SCREEN',array(
			'_GUID'=>'f508d483459b88fab44cd8b9f4db780b',
			'TITLE'=>$this->title,
			'SUBMITTER'=>strval($myrow['submitter']),
			'I_TITLE'=>get_translated_tempcode($myrow['i_title']),
			'CAPTION'=>get_translated_tempcode($myrow['caption']),
			'DATE_RAW'=>$date_raw,
			'ADD_DATE_RAW'=>$add_date_raw,
			'EDIT_DATE_RAW'=>$edit_date_raw,
			'DATE'=>$date,
			'ADD_DATE'=>$add_date,
			'EDIT_DATE'=>$edit_date,
			'VIEWS'=>integer_format($myrow['iotd_views']),
			'TRACKBACK_DETAILS'=>$trackback_details,
			'RATING_DETAILS'=>$rating_details,
			'COMMENT_DETAILS'=>$comment_details,
			'EDIT_URL'=>$edit_url,
			'URL'=>$url,
		));
	}

}


