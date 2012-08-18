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
 * @package		ocf_clubs
 */

require_code('crud_module');

/**
 * Module page class.
 */
class Module_cms_ocf_groups extends standard_crud_module
{
	var $lang_type='CLUB';
	var $select_name='NAME';
	var $award_type='group';
	var $possibly_some_kind_of_upload=true;
	var $output_of_action_is_confirmation=true;
	var $menu_label='CLUBS';
	var $do_preview=NULL;
	var $view_entry_point='_SEARCH:groups:view:id=_ID';
	var $orderer='g_name';

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array_merge(array('misc'=>'MANAGE_CLUBS'),parent::get_entry_points());
	}

	/**
	 * Standard crud_module run_start.
	 *
	 * @param  ID_TEXT		The type of module execution
	 * @return tempcode		The output of the run
	 */
	function run_start($type)
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/clubs';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_subcom';

		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();
		require_code('ocf_groups_action');
		require_code('ocf_forums_action');
		require_code('ocf_groups_action2');
		require_code('ocf_forums_action2');

		$this->add_one_label=do_lang_tempcode('ADD_CLUB');
		$this->edit_this_label=do_lang_tempcode('EDIT_THIS_CLUB');
		$this->edit_one_label=do_lang_tempcode('EDIT_CLUB');

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
		return do_next_manager(get_screen_title('MANAGE_CLUBS'),comcode_lang_string('DOC_CLUBS'),
					array(
						/*	 type							  page	 params													 zone	  */
						array('add_one',array('_SELF',array('type'=>'ad'),'_SELF'),do_lang('ADD_CLUB')),
						array('edit_one',array('_SELF',array('type'=>'ed'),'_SELF'),do_lang('EDIT_CLUB')),
					),
					do_lang('MANAGE_CLUBS')
		);
	}

	/**
	 * Get tempcode for a adding/editing form.
	 *
	 * @param  ?GROUP			The usergroup being edited (NULL: adding, not editing, and let's choose the current member)
	 * @param  SHORT_TEXT	The usergroup name
	 * @param  ?ID_TEXT		The username of the usergroup leader (NULL: none picked yet)
	 * @param  BINARY			Whether members may join this usergroup without requiring any special permission
	 * @return tempcode		The input fields
	 */
	function get_form_fields($id=NULL,$name='',$group_leader=NULL,$open_membership=1)
	{
		if (is_null($group_leader)) $group_leader=$GLOBALS['FORUM_DRIVER']->get_username(get_member());

		$fields=new ocp_tempcode();
		require_code('form_templates');
		$fields->attach(form_input_line(do_lang_tempcode('NAME'),do_lang_tempcode('DESCRIPTION_USERGROUP_TITLE'),'name',$name,true));
		$fields->attach(form_input_username(do_lang_tempcode('GROUP_LEADER'),do_lang_tempcode('DESCRIPTION_GROUP_LEADER'),'group_leader',$group_leader,false));
		$fields->attach(form_input_tick(do_lang_tempcode('OPEN_MEMBERSHIP'),do_lang_tempcode('OPEN_MEMBERSHIP_DESCRIPTION'),'open_membership',$open_membership==1));

		return $fields;
	}

	/**
	 * Standard crud_module table function.
	 *
	 * @param  array			Details to go to build_url for link to the next screen.
	 * @return array			A quartet: The choose table, Whether re-ordering is supported from this screen, Search URL, Archive URL.
	 */
	function nice_get_choose_table($url_map)
	{
		require_code('templates_results_table');

		$default_order='g_name ASC';
		$current_ordering=get_param('sort',$default_order,true);
		$sortables=array(
			'g_name'=>do_lang_tempcode('NAME'),
		);
		if (strpos($current_ordering,' ')===false) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
		list($sortable,$sort_order)=explode(' ',$current_ordering,2);
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';

		$header_row=results_field_title(array(
			do_lang_tempcode('NAME'),
			do_lang_tempcode('OPEN_MEMBERSHIP'),
			do_lang_tempcode('ACTIONS'),
		),$sortables,'sort',$sortable.' '.$sort_order);

		$fields=new ocp_tempcode();

		$count=$GLOBALS['FORUM_DB']->query_select_value('f_groups','COUNT(*)',array('g_is_private_club'=>1));
		require_code('form_templates');
		list($rows,$max_rows)=$this->get_entry_rows(false,$current_ordering,($count>300 || (!has_specific_permission(get_member(),'control_usergroups')))?array('g_group_leader'=>get_member(),'g_is_private_club'=>1):array('g_is_private_club'=>1));
		foreach ($rows as $row)
		{
			$edit_link=build_url($url_map+array('id'=>$row['id']),'_SELF');

			$fr=array(
				protect_from_escaping(ocf_get_group_link($row['id'])),
				($row['g_open_membership']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),
			);

			$fr[]=protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,'#'.strval($row['id'])));

			$fields->attach(results_entry($fr,true));
		}

		$search_url=build_url(array('page'=>'search','id'=>'ocf_clubs'),get_module_zone('search'));
		$archive_url=build_url(array('page'=>'groups'),get_module_zone('groups'));

		return array(results_table(do_lang($this->menu_label),get_param_integer('start',0),'start',get_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order,'sort'),false,$search_url,$archive_url);
	}

	/**
	 * Standard crud_module list function.
	 *
	 * @return tempcode		The selection list
	 */
	function nice_get_entries()
	{
		$fields=new ocp_tempcode();
		$count=$GLOBALS['FORUM_DB']->query_select_value('f_groups','COUNT(*)',array('g_is_private_club'=>1));
		if ($count<500)
		{
			$rows=$GLOBALS['FORUM_DB']->query_select('f_groups',array('id','g_name','g_promotion_target','g_is_super_admin','g_group_leader'),array('g_is_private_club'=>1),'ORDER BY g_name');
		} else
		{
			$rows=$GLOBALS['FORUM_DB']->query_select('f_groups',array('id','g_name','g_promotion_target','g_is_super_admin','g_group_leader'),array('g_group_leader'=>get_member(),'g_is_private_club'=>1),'ORDER BY g_name');
			if (count($rows)==0) warn_exit(do_lang_tempcode('TOO_MANY_TO_CHOOSE_FROM'));
		}
		foreach ($rows as $row)
		{
			$is_super_admin=$row['g_is_super_admin'];
			if ((!has_specific_permission(get_member(),'control_usergroups')) || ($is_super_admin==1))
			{
				$leader=$row['g_group_leader'];
				if ($leader!=get_member()) continue;
			}
			$fields->attach(form_input_list_entry(strval($row['id']),false,get_translated_text($row['g_name'],$GLOBALS['FORUM_DB'])));
		}

		return $fields;
	}

	/**
	 * Standard crud_module edit form filler.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return tempcode		Fields
	 */
	function fill_in_edit_form($id)
	{
		$rows=$GLOBALS['FORUM_DB']->query_select('f_groups',array('*'),array('id'=>intval($id),'g_is_private_club'=>1));
		if (!array_key_exists(0,$rows))
		{
			warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		}
		$myrow=$rows[0];

		$username=$GLOBALS['FORUM_DRIVER']->get_username($myrow['g_group_leader']);
		if (is_null($username)) $username='';//do_lang('UNKNOWN');
		$fields=$this->get_form_fields($id,get_translated_text($myrow['g_name'],$GLOBALS['FORUM_DB']),$username,$myrow['g_open_membership']);

		return $fields;
	}

	/**
	 * Standard crud_module add actualiser.
	 *
	 * @return ID_TEXT		The entry added
	 */
	function add_actualisation()
	{
		require_code('ocf_forums_action2');

		$_group_leader=post_param('group_leader');
		if ($_group_leader!='')
		{
			$group_leader=$GLOBALS['FORUM_DRIVER']->get_member_from_username($_group_leader);
			if (is_null($group_leader)) warn_exit(do_lang_tempcode('_USER_NO_EXIST',$_group_leader));
		} else $group_leader=NULL;

		$name=post_param('name');
		$id=ocf_make_group($name,0,0,0,'','',NULL,NULL,$group_leader,0,0,0,0,0,0,0,0,0,0,0,0,0,1000,0,post_param_integer('open_membership',0),1);

		// Create forum
		$mods=$GLOBALS['FORUM_DRIVER']->get_moderator_groups();
		$access_mapping=array();
		foreach ($mods as $m_id)
		{
			$access_mapping[$m_id]=5;
		}
		$_cat=get_option('club_forum_parent_category');
		if (is_numeric($_cat))
		{
			$cat=intval($_cat);
		} else
		{
			$cat=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_categories','id',array('c_title'=>$_cat));
			if (is_null($cat)) $cat=$GLOBALS['FORUM_DB']->query_select_value('f_categories','MIN(id)');
		}
		$_forum=get_option('club_forum_parent_forum');
		if (is_numeric($_forum))
		{
			$forum=intval($_forum);
		} else
		{
			$forum=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_forums','id',array('f_name'=>$_forum));
			if (is_null($forum)) $forum=$GLOBALS['FORUM_DB']->query_select_value('f_forums','MIN(id)');
		}
		$is_threaded=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_forums','f_is_threaded',array('id'=>$forum));
		$forum_id=ocf_make_forum($name,do_lang('FORUM_FOR_CLUB',$name),$cat,$access_mapping,$forum,1,1,0,'','','','last_post',$is_threaded);
		$this->_set_permissions($id,$forum_id);

		require_code('ocf_groups_action2');
		ocf_add_member_to_group(get_member(),$id);

		if (has_actual_page_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'groups'))
			syndicate_described_activity('ocf:ACTIVITY_ADD_CLUB',$name,'','','_SEARCH:groups:view:'.strval($id),'','','ocf_clubs');

		return strval($id);
	}

	/**
	 * Fix club's permissons (in case e.g. forum was recreated).
	 *
	 * @param  AUTO_LINK		Club (usergroup) ID
	 * @param  AUTO_LINK		Forum ID
	 */
	function _set_permissions($id,$forum_id)
	{
		// Cleanup
		$GLOBALS['FORUM_DB']->query_delete('gsp',array('group_id'=>$id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id)));
		$GLOBALS['FORUM_DB']->query_delete('group_category_access',array(
			'module_the_name'=>'forums',
			'category_name'=>strval($forum_id),
			'group_id'=>$id
		));

		// Create permissions
		$GLOBALS['FORUM_DB']->query_insert('gsp',array('specific_permission'=>'submit_midrange_content','group_id'=>$id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id),'the_value'=>1));
		$GLOBALS['FORUM_DB']->query_insert('gsp',array('specific_permission'=>'edit_own_midrange_content','group_id'=>$id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id),'the_value'=>1));
		$GLOBALS['FORUM_DB']->query_insert('gsp',array('specific_permission'=>'bypass_validation_midrange_content','group_id'=>$id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id),'the_value'=>1));
		$GLOBALS['FORUM_DB']->query_insert('gsp',array('specific_permission'=>'submit_lowrange_content','group_id'=>$id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id),'the_value'=>1));
		$GLOBALS['FORUM_DB']->query_insert('gsp',array('specific_permission'=>'edit_own_lowrange_content','group_id'=>$id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id),'the_value'=>1));
		$GLOBALS['FORUM_DB']->query_insert('gsp',array('specific_permission'=>'delete_own_lowrange_content','group_id'=>$id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id),'the_value'=>1));
		$GLOBALS['FORUM_DB']->query_insert('gsp',array('specific_permission'=>'bypass_validation_lowrange_content','group_id'=>$id,'the_page'=>'','module_the_name'=>'forums','category_name'=>strval($forum_id),'the_value'=>1));
		$GLOBALS['FORUM_DB']->query_insert('group_category_access',array(
			'module_the_name'=>'forums',
			'category_name'=>strval($forum_id),
			'group_id'=>$id
		));
	}

	/**
	 * Standard crud_module edit actualiser.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return ?tempcode		Confirm message (NULL: continue)
	 */
	function edit_actualisation($id)
	{
		$group_id=intval($id);
		require_code('ocf_groups');
		$leader=ocf_get_group_property($group_id,'group_leader');
		$is_super_admin=ocf_get_group_property($group_id,'is_super_admin');
		if ((!has_specific_permission(get_member(),'control_usergroups')) || ($is_super_admin==1))
		{
			if ($leader!=get_member()) access_denied('I_ERROR');
		}

		$old_name=ocf_get_group_name($group_id);

		$_group_leader=post_param('group_leader');
		if ($_group_leader!='')
		{
			$group_leader=$GLOBALS['FORUM_DRIVER']->get_member_from_username($_group_leader);
			if (is_null($group_leader)) warn_exit(do_lang_tempcode('_USER_NO_EXIST',$_group_leader));
		} else $group_leader=NULL;

		$name=post_param('name');

		ocf_edit_group($group_id,$name,NULL,NULL,NULL,NULL,NULL,NULL,NULL,$group_leader,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,post_param_integer('open_membership',0),1);

		$forum_where=array('f_name'=>$old_name,'f_category_id'=>intval(get_option('club_forum_parent_category')),'f_parent_forum'=>intval(get_option('club_forum_parent_forum')));
		$forum_id=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_forums','id',$forum_where);
		if (!is_null($forum_id))
		{
			$this->_set_permissions(intval($id),$forum_id);
		}

		// Rename forum
		if ($name!=$old_name)
		{
			$GLOBALS['FORUM_DB']->query_update('f_forums',array('f_name'=>$name),$forum_where,'ORDER BY id DESC',1);
		}

		return NULL;
	}

	/**
	 * Standard crud_module delete actualiser.
	 *
	 * @param  ID_TEXT		The entry being deleted
	 */
	function delete_actualisation($id)
	{
		$group_id=intval($id);
		require_code('ocf_groups');
		$leader=ocf_get_group_property($group_id,'group_leader');
		$is_super_admin=ocf_get_group_property($group_id,'is_super_admin');
		if ((!has_specific_permission(get_member(),'control_usergroups')) || ($is_super_admin==1))
		{
			if ($leader!=get_member()) access_denied('I_ERROR');
		}
		ocf_delete_group($group_id);
	}
}


