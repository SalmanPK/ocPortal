<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		tickets
 */

/**
 * Module page class.
 */
class Module_admin_tickets
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
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
	 * @param  boolean	Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points($check_perms=true,$member_id=NULL,$support_crosslinks=true,$be_deferential=false)
	{
		return array(
			'misc'=>array('MANAGE_TICKET_TYPES','menu/site_meta/tickets'),
		);
	}

	var $title;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_lang('tickets');

		set_helper_panel_tutorial('tut_support_desk');

		if ($type!='misc')
		{
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('MANAGE_TICKET_TYPES'))));
		}

		if ($type=='misc')
		{
			$this->title=get_screen_title('MANAGE_TICKET_TYPES');
		}

		if ($type=='add')
		{
			$this->title=get_screen_title('ADD_TICKET_TYPE');
		}

		if ($type=='edit')
		{
			$this->title=get_screen_title('EDIT_TICKET_TYPE');
		}

		if ($type=='_edit')
		{
			if (post_param_integer('delete',0)==1)
			{
				$this->title=get_screen_title('DELETE_TICKET_TYPE');
			} else
			{
				$this->title=get_screen_title('EDIT_TICKET_TYPE');
			}
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
		require_css('tickets');
		require_code('tickets');
		require_code('tickets2');

		$type=get_param('type','misc');

		if ($type=='misc') return $this->ticket_type_interface();
		if ($type=='add') return $this->add_ticket_type();
		if ($type=='edit') return $this->edit_ticket_type();
		if ($type=='_edit') return $this->_edit_ticket_type();

		return new ocp_tempcode();
	}

	/**
	 * The UI to choose a ticket type to edit, or to add a ticket.
	 *
	 * @return tempcode		The UI
	 */
	function ticket_type_interface()
	{
		require_lang('permissions');

		$list=new ocp_tempcode();
		require_code('form_templates');
		$ticket_types=collapse_1d_complexity('ticket_type',$GLOBALS['SITE_DB']->query_select('ticket_types',array('*'),NULL,'ORDER BY ticket_type'));
		foreach ($ticket_types as $ticket_type)
		{
			$list->attach(form_input_list_entry(strval($ticket_type),false,get_translated_text($ticket_type)));
		}
		if (!$list->is_empty())
		{
			$edit_url=build_url(array('page'=>'_SELF','type'=>'edit'),'_SELF',NULL,false,true);
			$submit_name=do_lang_tempcode('EDIT');
			$fields=form_input_list(do_lang_tempcode('TITLE'),do_lang_tempcode('DESCRIPTION_TICKET_TYPE'),'ticket_type',$list);

			$tpl=do_template('FORM',array('_GUID'=>'2d2e76f5cfc397a78688db72170918d4','TABINDEX'=>strval(get_form_field_tabindex()),'GET'=>true,'HIDDEN'=>'','TEXT'=>'','FIELDS'=>$fields,'URL'=>$edit_url,'SUBMIT_ICON'=>'menu___generic_admin__edit_this_category','SUBMIT_NAME'=>$submit_name));
		} else $tpl=new ocp_tempcode();

		// Do a form so people can add
		$post_url=build_url(array('page'=>'_SELF','type'=>'add'),'_SELF');
		$submit_name=do_lang_tempcode('ADD_TICKET_TYPE');
		$fields=form_input_line(do_lang_tempcode('TITLE'),do_lang_tempcode('DESCRIPTION_TICKET_TYPE'),'ticket_type_2','',false);
		$fields->attach(form_input_tick(do_lang_tempcode('TICKET_GUEST_EMAILS_MANDATORY'),do_lang_tempcode('DESCRIPTION_TICKET_GUEST_EMAILS_MANDATORY'),'guest_emails_mandatory',false));
		$fields->attach(form_input_tick(do_lang_tempcode('TICKET_SEARCH_FAQ'),do_lang_tempcode('DESCRIPTION_TICKET_SEARCH_FAQ'),'search_faq',false));
		// Permissions
		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'87ef39b0a5c3c45c1c1319c7f85d0e2a','TITLE'=>do_lang_tempcode('PERMISSIONS'),'SECTION_HIDDEN'=>true)));
		$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
		$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
		foreach ($groups as $id=>$group_name)
		{
			if (in_array($id,$admin_groups)) continue;
			$fields->attach(form_input_tick(do_lang_tempcode('ACCESS_FOR',escape_html($group_name)),do_lang_tempcode('DESCRIPTION_ACCESS_FOR',escape_html($group_name)),'access_'.strval($id),true));
		}
		$add_form=do_template('FORM',array('_GUID'=>'382f6fab6c563d81303ecb26495e76ec','TABINDEX'=>strval(get_form_field_tabindex()),'SECONDARY_FORM'=>true,'HIDDEN'=>'','TEXT'=>'','FIELDS'=>$fields,'SUBMIT_ICON'=>'menu___generic_admin__add_one_category','SUBMIT_NAME'=>$submit_name,'URL'=>$post_url));

		return do_template('SUPPORT_TICKET_TYPE_SCREEN',array('_GUID'=>'28645dc4a86086fa865ec7e166b84bb6','TITLE'=>$this->title,'TPL'=>$tpl,'ADD_FORM'=>$add_form));
	}

	/**
	 * The actualiser to add a ticket type.
	 *
	 * @return tempcode		The UI
	 */
	function add_ticket_type()
	{
		$ticket_type=post_param('ticket_type',post_param('ticket_type_2'));
		add_ticket_type($ticket_type,post_param_integer('guest_emails_mandatory',0),post_param_integer('search_faq',0));

		// Permissions
		require_code('permissions2');
		set_category_permissions_from_environment('tickets',$ticket_type);

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
		return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The UI to edit a ticket.
	 *
	 * @return tempcode		The UI
	 */
	function edit_ticket_type()
	{
		require_code('form_templates');
		require_code('permissions2');

		$ticket_type=get_param_integer('ticket_type');
		$details=get_ticket_type($ticket_type);
		$type_text=get_translated_text($ticket_type);

		$post_url=build_url(array('page'=>'_SELF','type'=>'_edit','ticket_type'=>$ticket_type),'_SELF');

		$submit_name=do_lang_tempcode('SAVE');

		$fields=new ocp_tempcode();
		$fields->attach(form_input_line(do_lang_tempcode('TYPE'),do_lang_tempcode('DESCRIPTION_TICKET_TYPE'),'new_type',$type_text,false));
		$fields->attach(form_input_tick(do_lang_tempcode('TICKET_GUEST_EMAILS_MANDATORY'),do_lang_tempcode('DESCRIPTION_TICKET_GUEST_EMAILS_MANDATORY'),'guest_emails_mandatory',$details['guest_emails_mandatory']));
		$fields->attach(form_input_tick(do_lang_tempcode('TICKET_SEARCH_FAQ'),do_lang_tempcode('DESCRIPTION_TICKET_SEARCH_FAQ'),'search_faq',$details['search_faq']));
		$fields->attach(get_category_permissions_for_environment('tickets',$type_text));
		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'09e6f1d2276ee679f280b33a79bff089','TITLE'=>do_lang_tempcode('ACTIONS'))));
		$fields->attach(form_input_tick(do_lang_tempcode('DELETE'),do_lang_tempcode('DESCRIPTION_DELETE'),'delete',false));

		return do_template('FORM_SCREEN',array('_GUID'=>'0a505a779c1639fd2d3ee10c24a7905a','SKIP_VALIDATION'=>true,'TITLE'=>$this->title,'HIDDEN'=>'','TEXT'=>'','FIELDS'=>$fields,'SUBMIT_ICON'=>'menu___generic_admin__edit_this','SUBMIT_NAME'=>$submit_name,'URL'=>$post_url));
	}

	/**
	 * The actualiser to edit/delete a ticket type.
	 *
	 * @return tempcode		The UI
	 */
	function _edit_ticket_type()
	{
		$type=get_param_integer('ticket_type');

		if (post_param_integer('delete',0)==1)
		{
			delete_ticket_type($type);
		} else
		{
			$trans_old_ticket_type=get_translated_text($type);
			edit_ticket_type($type,post_param('new_type'),post_param_integer('guest_emails_mandatory',0),post_param_integer('search_faq',0));

			$GLOBALS['SITE_DB']->query_delete('group_category_access',array('module_the_name'=>'tickets','category_name'=>$trans_old_ticket_type),'',1);
			require_code('permissions2');
			set_category_permissions_from_environment('tickets',post_param('new_type'));
		}

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
		return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
	}
}


