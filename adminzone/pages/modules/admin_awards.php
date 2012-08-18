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
 * @package		awards
 */

require_code('crud_module');

/**
 * Module page class.
 */
class Module_admin_awards extends standard_crud_module
{
	var $lang_type='AWARD_TYPE';
	var $select_name='TITLE';
	var $archive_entry_point='_SEARCH:awards';
	var $archive_label='VIEW_PAST_WINNERS';
	var $permission_module='award';
	var $menu_label='AWARDS';
	var $table='award_types';
	var $orderer='a_title';
	var $title_is_multi_lang=true;

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
		$info['update_require_upgrade']=1;
		$info['version']=3;
		$info['locked']=true;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('award_archive');
		$GLOBALS['SITE_DB']->drop_table_if_exists('award_types');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		if ((!is_null($upgrade_from)) && ($upgrade_from<3))
		{
			delete_privilege('choose_dotw');
			delete_config_option('dotw_update_time');
			$GLOBALS['SITE_DB']->rename_table('dotw','award_archive');
			$GLOBALS['SITE_DB']->add_table_field('award_archive','a_type_id','AUTO_LINK',db_get_first_id());
			$GLOBALS['SITE_DB']->alter_table_field('award_archive','id','ID_TEXT','content_id');
			$GLOBALS['SITE_DB']->add_table_field('award_archive','member_id','USER',$GLOBALS['FORUM_DRIVER']->get_guest_id());
			$GLOBALS['SITE_DB']->change_primary_key('award_archive',array('a_type_id','date_and_time'));
		}

		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('award_archive',array(
				'a_type_id'=>'*AUTO_LINK',
				'date_and_time'=>'*TIME',
				'content_id'=>'ID_TEXT',
				'member_id'=>'USER'
			));
		}

		$GLOBALS['SITE_DB']->create_index('award_archive','awardquicksearch',array('content_id'));

		if (((!is_null($upgrade_from)) && ($upgrade_from<3)) || (is_null($upgrade_from)))
		{
			$GLOBALS['SITE_DB']->create_table('award_types',array(
				'id'=>'*AUTO',
				'a_title'=>'SHORT_TRANS',
				'a_description'=>'LONG_TRANS',
				'a_points'=>'INTEGER',
				'a_content_type'=>'ID_TEXT', // uses same naming convention as ocp_merge importer
				'a_hide_awardee'=>'BINARY',
				'a_update_time_hours'=>'INTEGER',
			));

			require_lang('awards');
			$GLOBALS['SITE_DB']->query_insert('award_types',array(
				'a_title'=>lang_code_to_default_content('DOTW'),
				'a_description'=>lang_code_to_default_content('DESCRIPTION_DOTW'),
				'a_points'=>0,
				'a_content_type'=>'download',
				'a_hide_awardee'=>1,
				'a_update_time_hours'=>168
			));
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array_merge(array('misc'=>'MANAGE_AWARDS'),parent::get_entry_points());
	}

	/**
	 * Standard crud_module run_start.
	 *
	 * @param  ID_TEXT		The type of module execution
	 * @return tempcode		The output of the run
	 */
	function run_start($type)
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/awards';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_featured';

		require_code('awards');

		$this->add_text=do_lang_tempcode('AWARD_ALLOCATEHELP');

		$this->add_one_label=do_lang_tempcode('ADD_AWARD_TYPE');
		$this->edit_this_label=do_lang_tempcode('EDIT_THIS_AWARD_TYPE');
		$this->edit_one_label=do_lang_tempcode('EDIT_AWARD_TYPE');

		if ($type=='misc') return $this->misc();
		return new ocp_tempcode();
	}

	/**
	 * The do-next manager for before content management.
	 *
	 * @return tempcode		The UI
	 */
	function misc()
	{
		require_code('templates_donext');
		return do_next_manager(get_screen_title('MANAGE_AWARDS'),comcode_lang_string('DOC_AWARDS'),
					array(
						/*	 type							  page	 params													 zone	  */
						array('add_one',array('_SELF',array('type'=>'ad'),'_SELF'),do_lang('ADD_AWARD_TYPE')),
						array('edit_one',array('_SELF',array('type'=>'ed'),'_SELF'),do_lang('EDIT_AWARD_TYPE')),
					),
					do_lang('MANAGE_AWARDS')
		);
	}

	/**
	 * Standard crud_module table function.
	 *
	 * @param  array			Details to go to build_url for link to the next screen.
	 * @return array			A pair: The choose table, Whether re-ordering is supported from this screen.
	 */
	function nice_get_choose_table($url_map)
	{
		require_code('templates_results_table');

		$hr=array();
		$hr[]=do_lang_tempcode('TITLE');
		if (addon_installed('points'))
			$hr[]=do_lang_tempcode('POINTS');
		$hr[]=do_lang_tempcode('CONTENT_TYPE');
		$hr[]=do_lang_tempcode('USED_PREVIOUSLY');
		$hr[]=do_lang_tempcode('ACTIONS');

		$current_ordering=get_param('sort','a_title ASC');
		if (strpos($current_ordering,' ')===false) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
		list($sortable,$sort_order)=explode(' ',$current_ordering,2);
		$sortables=array(
			'a_title'=>do_lang_tempcode('TITLE'),
			'a_content_type'=>do_lang_tempcode('CONTENT_TYPE'),
		);
		if (addon_installed('points'))
			$sortables['a_points']=do_lang_tempcode('POINTS');
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';

		$header_row=results_field_title($hr,$sortables,'sort',$sortable.' '.$sort_order);

		$fields=new ocp_tempcode();

		require_code('form_templates');
		list($rows,$max_rows)=$this->get_entry_rows(false,$current_ordering);
		foreach ($rows as $row)
		{
			$edit_link=build_url($url_map+array('id'=>$row['id']),'_SELF');

			$fr=array();
			$fr[]=protect_from_escaping(hyperlink(build_url(array('page'=>'awards','type'=>'award','id'=>$row['id']),get_module_zone('awards')),get_translated_text($row['a_title'])));
			if (addon_installed('points'))
				$fr[]=integer_format($row['a_points']);
			$hooks=find_all_hooks('systems','awards');
			$hook_title=do_lang('UNKNOWN');
			foreach (array_keys($hooks) as $hook)
			{
				if ($hook==$row['a_content_type'])
				{
					require_code('hooks/systems/awards/'.$hook);
					$hook_object=object_factory('Hook_awards_'.$hook,true);
					if (is_null($hook_object)) continue;
					$hook_info=$hook_object->info();
					if (!is_null($hook_info))
						$hook_title=$hook_info['title']->evaluate();
				}
			}
			$fr[]=$hook_title;
			$fr[]=integer_format($GLOBALS['SITE_DB']->query_select_value('award_archive','COUNT(*)',array('a_type_id'=>$row['id'])));
			$fr[]=protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,'#'.strval($row['id'])));

			$fields->attach(results_entry($fr,true));
		}

		return array(results_table(do_lang($this->menu_label),get_param_integer('start',0),'start',get_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order),false);
	}

	/**
	 * Get tempcode for adding/editing form.
	 *
	 * @param  ?AUTO_LINK	The ID of the award (NULL: not added yet)
	 * @param  SHORT_TEXT	The title
	 * @param  LONG_TEXT		The description
	 * @param  integer		How many points are given to the awardee
	 * @param  ID_TEXT		The content type the award type is for
	 * @param  BINARY			Whether to not show the awardee when displaying this award
	 * @param  integer		The approximate time in hours between awards (e.g. 168 for a week)
	 * @return tempcode		The input fields
	 */
	function get_form_fields($id=NULL,$title='',$description='',$points=0,$content_type='download',$hide_awardee=0,$update_time_hours=168)
	{
		$fields=new ocp_tempcode();
		$fields->attach(form_input_line(do_lang_tempcode('TITLE'),do_lang_tempcode('DESCRIPTION_TITLE'),'title',$title,true));
		$fields->attach(form_input_text_comcode(do_lang_tempcode('DESCRIPTION'),do_lang_tempcode('DESCRIPTION_DESCRIPTION'),'description',$description,true));
		if (addon_installed('points'))
		{
			$fields->attach(form_input_integer(do_lang_tempcode('POINTS'),do_lang_tempcode('DESCRIPTION_AWARD_POINTS'),'points',$points,true));
		}
		$list=new ocp_tempcode();
		$_hooks=array();
		$hooks=find_all_hooks('systems','awards');
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/systems/awards/'.$hook);
			$hook_object=object_factory('Hook_awards_'.$hook,true);
			if (is_null($hook_object)) continue;
			$hook_info=$hook_object->info();
			if (!is_null($hook_info))
				$_hooks[$hook]=$hook_info['title']->evaluate();
		}
		asort($_hooks);
		foreach ($_hooks as $hook=>$hook_title)
		{
			$list->attach(form_input_list_entry($hook,$hook==$content_type,protect_from_escaping($hook_title)));
		}
		if ($list->is_empty()) inform_exit(do_lang_tempcode('NO_CATEGORIES'));
		$fields->attach(form_input_list(do_lang_tempcode('CONTENT_TYPE'),do_lang_tempcode('DESCRIPTION_CONTENT_TYPE'),'content_type',$list));
		$fields->attach(form_input_tick(do_lang_tempcode('HIDE_AWARDEE'),do_lang_tempcode('DESCRIPTION_HIDE_AWARDEE'),'hide_awardee',$hide_awardee==1));
		$fields->attach(form_input_integer(do_lang_tempcode('AWARD_UPDATE_TIME_HOURS'),do_lang_tempcode('DESCRIPTION_AWARD_UPDATE_TIME_HOURS'),'update_time_hours',$update_time_hours,true));

		// Permissions
		$fields->attach($this->get_permission_fields(is_null($id)?NULL:strval($id),do_lang_tempcode('AWARD_PERMISSION_HELP'),false/*We want permissions off by default so we do not say new category is_null($id)*/,do_lang_tempcode('GIVE_AWARD')));

		return $fields;
	}

	/**
	 * Standard crud_module list function.
	 *
	 * @return tempcode		The selection list
	 */
	function nice_get_entries()
	{
		$_m=$GLOBALS['SITE_DB']->query_select('award_types',array('id','a_title'));
		$entries=new ocp_tempcode();
		foreach ($_m as $m)
		{
			$entries->attach(form_input_list_entry(strval($m['id']),false,get_translated_text($m['a_title'])));
		}

		return $entries;
	}

	/**
	 * Standard crud_module edit form filler.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return tempcode		The edit form
	 */
	function fill_in_edit_form($id)
	{
		$m=$GLOBALS['SITE_DB']->query_select('award_types',array('*'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$m)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$r=$m[0];

		$fields=$this->get_form_fields(intval($id),get_translated_text($r['a_title']),get_translated_text($r['a_description']),$r['a_points'],$r['a_content_type'],$r['a_hide_awardee'],$r['a_update_time_hours']);

		return $fields;
	}

	/**
	 * Standard crud_module add actualiser.
	 *
	 * @return ID_TEXT		The entry added
	 */
	function add_actualisation()
	{
		$id=add_award_type(post_param('title'),post_param('description'),post_param_integer('points',0),post_param('content_type'),post_param_integer('hide_awardee',0),post_param_integer('update_time_hours'));

		$this->set_permissions($id);

		return strval($id);
	}

	/**
	 * Standard crud_module edit actualiser.
	 *
	 * @param  ID_TEXT		The entry being edited
	 */
	function edit_actualisation($id)
	{
		edit_award_type(intval($id),post_param('title'),post_param('description'),post_param_integer('points',0),post_param('content_type'),post_param_integer('hide_awardee',0),post_param_integer('update_time_hours'));

		$this->set_permissions($id);
	}

	/**
	 * Standard crud_module delete actualiser.
	 *
	 * @param  ID_TEXT		The entry being deleted
	 */
	function delete_actualisation($id)
	{
		delete_award_type(intval($id));
	}

}


