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
 * @package		banners
 */

/**
 * Module page class.
 */
class Module_banners
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
		$info['version']=6;
		$info['locked']=true;
		$info['update_require_upgrade']=1;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('banners');
		$GLOBALS['SITE_DB']->drop_if_exists('banner_types');
		$GLOBALS['SITE_DB']->drop_if_exists('banner_clicks');

		$GLOBALS['SITE_DB']->query_delete('group_category_access',array('module_the_name'=>'banners'));

		delete_config_option('is_on_banners');
		delete_config_option('money_ad_code');
		delete_config_option('use_banner_permissions');
		delete_config_option('advert_chance');
		delete_config_option('points_ADD_BANNER');
		delete_config_option('admin_banners');
		delete_config_option('banner_autosize');

		delete_specific_permission('full_banner_setup');
		delete_specific_permission('view_anyones_banner_stats');
		delete_specific_permission('banner_free');
		delete_specific_permission('use_html_banner');
		delete_specific_permission('use_php_banner');

		deldir_contents(get_custom_file_base().'/uploads/banners',true);

		//delete_menu_item_simple('_SEARCH:donate');
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
			$GLOBALS['SITE_DB']->create_table('banners',array(
				'name'=>'*ID_TEXT',
				'expiry_date'=>'?TIME',
				'submitter'=>'USER',
				'img_url'=>'URLPATH',
				'the_type'=>'SHORT_INTEGER', // 0=permanent|1=campaign|2=default
				'b_title_text'=>'SHORT_TEXT',
				'caption'=>'SHORT_TRANS',	// Comcode
				'b_direct_code'=>'LONG_TEXT',
				'campaign_remaining'=>'INTEGER',
				'site_url'=>'URLPATH',
				'hits_from'=>'INTEGER',
				'views_from'=>'INTEGER',
				'hits_to'=>'INTEGER',
				'views_to'=>'INTEGER',
				'importance_modulus'=>'INTEGER',
				'notes'=>'LONG_TEXT',
				'validated'=>'BINARY',
				'add_date'=>'TIME',
				'edit_date'=>'?TIME',
				'b_type'=>'ID_TEXT'
			));

			$GLOBALS['SITE_DB']->create_index('banners','banner_child_find',array('b_type'));
			$GLOBALS['SITE_DB']->create_index('banners','the_type',array('the_type'));
			$GLOBALS['SITE_DB']->create_index('banners','expiry_date',array('expiry_date'));
			$GLOBALS['SITE_DB']->create_index('banners','badd_date',array('add_date'));
			$GLOBALS['SITE_DB']->create_index('banners','topsites',array('hits_from','hits_to'));
			$GLOBALS['SITE_DB']->create_index('banners','campaign_remaining',array('campaign_remaining'));
			$GLOBALS['SITE_DB']->create_index('banners','bvalidated',array('validated'));

			$GLOBALS['SITE_DB']->query_insert('banners',array('name'=>'advertise_here','b_title_text'=>'','b_direct_code'=>'','the_type'=>2,'img_url'=>'data/images/advertise_here.png','caption'=>lang_code_to_default_content('ADVERTISE_HERE',true,1),'campaign_remaining'=>0,'site_url'=>get_base_url().'/site/index.php?page=advertise','hits_from'=>0,'views_from'=>0,'hits_to'=>0,'views_to'=>0,'importance_modulus'=>10,'notes'=>'Provided as default. This is a default banner (it shows when others are not available).','validated'=>1,'add_date'=>time(),'submitter'=>$GLOBALS['FORUM_DRIVER']->get_guest_id(),'b_type'=>'','expiry_date'=>NULL,'edit_date'=>NULL));
			$GLOBALS['SITE_DB']->query_insert('banners',array('name'=>'donate','b_title_text'=>'','b_direct_code'=>'','the_type'=>0,'img_url'=>'data/images/donate.png','caption'=>lang_code_to_default_content('DONATION',true,1),'campaign_remaining'=>0,'site_url'=>get_base_url().'/site/index.php?page=donate','hits_from'=>0,'views_from'=>0,'hits_to'=>0,'views_to'=>0,'importance_modulus'=>30,'notes'=>'Provided as default.','validated'=>1,'add_date'=>time(),'submitter'=>$GLOBALS['FORUM_DRIVER']->get_guest_id(),'b_type'=>'','expiry_date'=>NULL,'edit_date'=>NULL));
			$banner_a='advertise_here';
			$banner_c='donate';

			$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
			foreach (array_keys($groups) as $group_id)
			{
				$GLOBALS['SITE_DB']->query_insert('group_category_access',array('module_the_name'=>'banners','category_name'=>$banner_a,'group_id'=>$group_id));
				$GLOBALS['SITE_DB']->query_insert('group_category_access',array('module_the_name'=>'banners','category_name'=>$banner_c,'group_id'=>$group_id));
			}

			add_specific_permission('BANNERS','full_banner_setup',false);
			add_specific_permission('BANNERS','view_anyones_banner_stats',false);

			add_config_option('ADD_BANNER','points_ADD_BANNER','integer','return addon_installed(\'points\')?\'0\':NULL;','POINTS','COUNT_POINTS_GIVEN');

			//add_menu_item_simple('main_website',NULL,'DONATE','_SEARCH:donate');
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<3))
		{
			$GLOBALS['SITE_DB']->add_table_field('banners','b_type','ID_TEXT');
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<4))
		{
			$GLOBALS['SITE_DB']->create_table('banner_types',array(
				'id'=>'*ID_TEXT',
				't_is_textual'=>'BINARY',
				't_image_width'=>'INTEGER',
				't_image_height'=>'INTEGER',
				't_max_file_size'=>'INTEGER',
				't_comcode_inline'=>'BINARY'
			));

			$GLOBALS['SITE_DB']->create_index('banner_types','hottext',array('t_comcode_inline'));

			$GLOBALS['SITE_DB']->query_insert('banner_types',array(
				'id'=>'',
				't_is_textual'=>0,
				't_image_width'=>468,
				't_image_height'=>60,
				't_max_file_size'=>80,
				't_comcode_inline'=>0
			));

			$GLOBALS['SITE_DB']->create_table('banner_clicks',array(
				'id'=>'*AUTO',
				'c_date_and_time'=>'TIME',
				'c_member_id'=>'USER',
				'c_ip_address'=>'IP',
				'c_source'=>'ID_TEXT',
				'c_banner_id'=>'ID_TEXT'
			));
			$GLOBALS['SITE_DB']->create_index('banner_clicks','clicker_ip',array('c_ip_address'));

			add_specific_permission('BANNERS','banner_free',false);

			add_config_option('PERMISSIONS','use_banner_permissions','tick','return \'0\';','FEATURE','BANNERS');

			$GLOBALS['SITE_DB']->query_update('banners',array('b_type'=>'BANNERS'),array('b_type'=>'_BANNERS'));
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<4))
		{
			$GLOBALS['SITE_DB']->add_table_field('banners','b_title_text','SHORT_TEXT');
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<5))
		{
			add_config_option('BANNER_AUTOSIZE','banner_autosize','tick','return is_null($old=get_value(\'banner_autosize\'))?\'0\':$old;','FEATURE','BANNERS');
			add_config_option('ADMIN_BANNERS','admin_banners','tick','return is_null($old=get_value(\'always_banners\'))?\'0\':$old;','FEATURE','BANNERS');
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<6))
		{
			$GLOBALS['SITE_DB']->add_table_field('banners','b_direct_code','LONG_TEXT');
			delete_config_option('money_ad_code');
			delete_config_option('advert_chance');
			delete_config_option('is_on_banners');
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<6))
		{
			add_specific_permission('BANNERS','use_html_banner',false);
			add_specific_permission('BANNERS','use_php_banner',false,true);
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'BANNERS');
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
		unset($require_permission_support);

		$permission_page='cms_banners';

		return array(array(),$permission_page);
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		if (get_file_base()!=get_custom_file_base()) warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));

		require_code('banners');
		require_lang('banners');

		// Decide what we're doing
		$type=get_param('type','misc');

		if ($type=='view') return $this->view_banner();
		if ($type=='misc') return $this->choose_banner();

		return new ocp_tempcode();
	}

	/**
	 * The UI to choose a banner to view.
	 *
	 * @return tempcode		The UI
	 */
	function choose_banner()
	{
		$title=get_screen_title('BANNERS');

		require_code('templates_results_table');

		$current_ordering=get_param('sort','name ASC');
		if (strpos($current_ordering,' ')===false) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
		list($sortable,$sort_order)=explode(' ',$current_ordering,2);
		$sortables=array(
			'name'=>do_lang_tempcode('CODENAME'),
			'b_type'=>do_lang_tempcode('_BANNER_TYPE'),
			'the_type'=>do_lang_tempcode('DEPLOYMENT_AGREEMENT'),
			//'campaign_remaining'=>do_lang_tempcode('HITS_ALLOCATED'),
			'importance_modulus'=>do_lang_tempcode('IMPORTANCE_MODULUS'),
			'expiry_date'=>do_lang_tempcode('EXPIRY_DATE'),
			'add_date'=>do_lang_tempcode('ADDED'),
		);
		if (addon_installed('unvalidated')) $sortables['validated']=do_lang_tempcode('VALIDATED');
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';

		$hr=array(
			do_lang_tempcode('CODENAME'),
			do_lang_tempcode('_BANNER_TYPE'),
			do_lang_tempcode('DEPLOYMENT_AGREEMENT'),
			//do_lang_tempcode('HITS_ALLOCATED'),
			do_lang_tempcode('IMPORTANCE_MODULUS'),
			do_lang_tempcode('EXPIRY_DATE'),
			do_lang_tempcode('ADDED'),
		);
		if (addon_installed('unvalidated')) $hr[]=do_lang_tempcode('VALIDATED');
		$hr[]=do_lang_tempcode('ACTIONS');
		$header_row=results_field_title($hr,$sortables,'sort',$sortable.' '.$sort_order);

		$fields=new ocp_tempcode();

		$url_map=array('page'=>'_SELF','type'=>'view');

		require_code('form_templates');
		$only_owned=has_specific_permission(get_member(),'edit_midrange_content','cms_banners')?NULL:get_member();
		$max_rows=$GLOBALS['SITE_DB']->query_value('banners','COUNT(*)',is_null($only_owned)?NULL:array('submitter'=>$only_owned));
		if ($max_rows==0) inform_exit(do_lang_tempcode('NO_ENTRIES'));
		$max=get_param_integer('max',20);
		$start=get_param_integer('start',0);
		$rows=$GLOBALS['SITE_DB']->query_select('banners',array('*'),is_null($only_owned)?NULL:array('submitter'=>$only_owned),'ORDER BY '.$current_ordering,$max,$start);
		foreach ($rows as $row)
		{
			$view_link=build_url($url_map+array('source'=>$row['name']),'_SELF');

			$deployment_agreement=new ocp_tempcode();
			switch ($row['the_type'])
			{
				case BANNER_PERMANENT:
					$deployment_agreement=do_lang_tempcode('BANNER_PERMANENT');
					break;
				case BANNER_CAMPAIGN:
					$deployment_agreement=do_lang_tempcode('BANNER_CAMPAIGN');
					break;
				case BANNER_DEFAULT:
					$deployment_agreement=do_lang_tempcode('BANNER_DEFAULT');
					break;
			}

			$fr=array(
				$row['name'],
				($row['b_type']=='')?do_lang('GENERAL'):$row['b_type'],
				$deployment_agreement,
				//integer_format($row['campaign_remaining']),
				strval($row['importance_modulus']),
				is_null($row['expiry_date'])?protect_from_escaping(do_lang_tempcode('NA_EM')):make_string_tempcode(get_timezoned_date($row['expiry_date'])),
				get_timezoned_date($row['add_date']),
			);
			if (addon_installed('unvalidated')) $fr[]=($row['validated']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO');
			$fr[]=protect_from_escaping(hyperlink($view_link,do_lang_tempcode('VIEW'),false,true,$row['name']));

			$fields->attach(results_entry($fr),true);
		}

		$table=results_table(do_lang('BANNERS'),get_param_integer('start',0),'start',get_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order);

		$text=do_lang_tempcode('CHOOSE_VIEW_LIST');

		return do_template('COLUMNED_TABLE_SCREEN',array('_GUID'=>'be5248da379faeead5a18d9f2b62bd6b','TITLE'=>$title,'TEXT'=>$text,'TABLE'=>$table,'SUBMIT_NAME'=>NULL,'POST_URL'=>get_self_url()));
	}

	/**
	 * The UI to view a banner.
	 *
	 * @return tempcode		The UI
	 */
	function view_banner()
	{
		$title=get_screen_title('BANNER_INFORMATION');

		$source=get_param('source');

		$rows=$GLOBALS['SITE_DB']->query_select('banners',array('*'),array('name'=>$source));
		if (!array_key_exists(0,$rows))
		{
			warn_exit(do_lang_tempcode('BANNER_MISSING_SOURCE'));
		}
		$myrow=$rows[0];

		if ((is_guest($myrow['submitter'])) || ($myrow['submitter']!=get_member()))
			check_specific_permission('view_anyones_banner_stats');

		switch ($myrow['the_type'])
		{
			case 0:
				$type=do_lang_tempcode('BANNER_PERMANENT');
				break;
			case 1:
				$type=do_lang_tempcode('_BANNER_HITS_LEFT',do_lang_tempcode('BANNER_CAMPAIGN'),make_string_tempcode(integer_format($myrow['campaign_remaining'])));
				break;
			case 2:
				$type=do_lang_tempcode('BANNER_DEFAULT');
				break;
		}

		if ($myrow['views_to']!=0)
			$click_through=protect_from_escaping(escape_html(float_format(round(100.0*($myrow['hits_to']/$myrow['views_to'])))));
		else $click_through=do_lang_tempcode('NA_EM');

		$has_banner_network=$GLOBALS['SITE_DB']->query_value('banners','SUM(views_from)')!=0.0;

		$fields=new ocp_tempcode();
		require_code('templates_map_table');
		$fields->attach(map_table_field(do_lang_tempcode('TYPE'),$type));
		if ($myrow['b_type']!='') $fields->attach(map_table_field(do_lang_tempcode('_BANNER_TYPE'),$myrow['b_type']));
		$expiry_date=is_null($myrow['expiry_date'])?do_lang_tempcode('NA_EM'):make_string_tempcode(escape_html(get_timezoned_date($myrow['expiry_date'],true)));
		$fields->attach(map_table_field(do_lang_tempcode('EXPIRY_DATE'),$expiry_date));
		if ($has_banner_network)
		{
			$fields->attach(map_table_field(do_lang_tempcode('BANNER_HITSFROM'),integer_format($myrow['hits_from']),false,'hits_from'));
			$fields->attach(map_table_field(do_lang_tempcode('BANNER_VIEWSFROM'),integer_format($myrow['views_from']),false,'views_from'));
		}
		$fields->attach(map_table_field(do_lang_tempcode('BANNER_HITSTO'),($myrow['site_url']=='')?do_lang_tempcode('CANT_TRACK'):protect_from_escaping(escape_html(integer_format($myrow['hits_to']))),false,'hits_to'));
		$fields->attach(map_table_field(do_lang_tempcode('BANNER_VIEWSTO'),($myrow['site_url']=='')?do_lang_tempcode('CANT_TRACK'):protect_from_escaping(escape_html(integer_format($myrow['views_to']))),false,'views_to'));
		$fields->attach(map_table_field(do_lang_tempcode('BANNER_CLICKTHROUGH'),$click_through));
		$username=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($myrow['submitter']);
		$fields->attach(map_table_field(do_lang_tempcode('SUBMITTER'),$username,true));

		$map_table=do_template('MAP_TABLE',array('_GUID'=>'eb97a46d8e9813da7081991d5beed270','WIDTH'=>'300','FIELDS'=>$fields));

		$banner=show_banner($myrow['name'],$myrow['b_title_text'],get_translated_tempcode($myrow['caption']),$myrow['b_direct_code'],$myrow['img_url'],$source,$myrow['site_url'],$myrow['b_type'],$myrow['submitter']);

		$edit_url=new ocp_tempcode();
		if ((has_actual_page_access(NULL,'cms_banners',NULL,NULL)) && (has_edit_permission('mid',get_member(),$myrow['submitter'],'cms_banners')))
		{
			$edit_url=build_url(array('page'=>'cms_banners','type'=>'_ed','id'=>$source),get_module_zone('cms_banners'));
		}

		$GLOBALS['META_DATA']+=array(
			'created'=>date('Y-m-d',$myrow['add_date']),
			'creator'=>$GLOBALS['FORUM_DRIVER']->get_username($myrow['submitter']),
			'publisher'=>'', // blank means same as creator
			'modified'=>is_null($myrow['edit_date'])?'':date('Y-m-d',$myrow['edit_date']),
			'type'=>'Banner',
			'title'=>get_translated_text($myrow['caption']),
			'identifier'=>'_SEARCH:banners:view:'.$source,
			'description'=>'',
			'image'=>$myrow['img_url'],
		);

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('CHOOSE'))));

		return do_template('BANNER_VIEW_SCREEN',array('_GUID'=>'ed923ae0682c6ed679c0efda688c49ea','TITLE'=>$title,'EDIT_URL'=>$edit_url,'MAP_TABLE'=>$map_table,'BANNER'=>$banner));
	}

}


