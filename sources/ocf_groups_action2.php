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
 * @package		core_ocf
 */

/**
 * Find whether a certain member may control a certain usergroup.
 *
 * @param  GROUP		The usergroup.
 * @param  MEMBER		The member.
 * @return boolean	The answer.
 */
function ocf_may_control_group($group_id,$member_id)
{
	$leader=ocf_get_group_property($group_id,'group_leader');
	$is_super_admin=ocf_get_group_property($group_id,'is_super_admin');
	return (($member_id===$leader) || ($GLOBALS['OCF_DRIVER']->is_super_admin($member_id)) || ((has_privilege($member_id,'control_usergroups')) && ($is_super_admin==0)));
}

/**
 * Edit a usergroup.
 *
 * @param  AUTO_LINK		The ID of the usergroup to edit.
 * @param  ?SHORT_TEXT	The name of the usergroup. (NULL: do not change)
 * @param  ?BINARY		Whether members are automatically put into the when they join. (NULL: do not change)
 * @param  ?BINARY		Whether members of this usergroup are all super administrators. (NULL: do not change)
 * @param  ?BINARY		Whether members of this usergroup are all super moderators. (NULL: do not change)
 * @param  ?SHORT_TEXT	The title for primary members of this usergroup that don't have their own title. (NULL: do not change)
 * @param  ?URLPATH		The rank image for this. (NULL: do not change)
 * @param  ?GROUP			The that members of this usergroup get promoted to at point threshold (NULL: no promotion prospects).
 * @param  ?integer		The point threshold for promotion (NULL: no promotion prospects).
 * @param  ?MEMBER		The leader of this usergroup (NULL: none).
 * @param  ?integer		The number of seconds that members of this usergroup must endure between submits (group 'best of' applies). (NULL: do not change)
 * @param  ?integer		The number of seconds that members of this usergroup must endure between accesses (group 'best of' applies). (NULL: do not change)
 * @param  ?integer		The number of megabytes that members of this usergroup may attach per day (group 'best of' applies). (NULL: do not change)
 * @param  ?integer		The number of attachments that members of this usergroup may attach to something (group 'best of' applies). (NULL: do not change)
 * @param  ?integer		The maximum avatar width that members of this usergroup may have (group 'best of' applies). (NULL: do not change)
 * @param  ?integer		The maximum avatar height that members of this usergroup may have (group 'best of' applies). (NULL: do not change)
 * @param  ?integer		The maximum post length that members of this usergroup may make (group 'best of' applies). (NULL: do not change)
 * @param  ?integer		The maximum signature length that members of this usergroup may make (group 'best of' applies). (NULL: do not change)
 * @param  ?integer		The number of gift points that members of this usergroup start with (group 'best of' applies). (NULL: do not change)
 * @param  ?integer		The number of gift points that members of this usergroup get per day (group 'best of' applies). (NULL: do not change)
 * @param  ?BINARY		Whether e-mail confirmation is needed for new IP addresses seen for any member of this usergroup (group 'best of' applies). (NULL: do not change)
 * @param  ?BINARY		Whether the is presented for joining at joining (implies anyone may be in the, but only choosable at joining) (NULL: do not change)
 * @param  ?BINARY		Whether the name and membership of the is hidden (NULL: do not change)
 * @param  ?integer		The display order this will be given, relative to other usergroups. Lower numbered usergroups display before higher numbered usergroups. (NULL: do not change)
 * @param  ?BINARY		Whether the rank image will not be shown for secondary membership (NULL: do not change)
 * @param  ?BINARY		Whether members may join this usergroup without requiring any special permission (NULL: do not change)
 * @param  ?BINARY		Whether this usergroup is a private club. Private clubs may be managed in the CMS zone, and do not have any special permissions - except over their own associated forum. (NULL: do not change)
 * @param  boolean		Whether to force the title as unique, if there's a conflict
 */
function ocf_edit_group($group_id,$name,$is_default,$is_super_admin,$is_super_moderator,$title,$rank_image,$promotion_target,$promotion_threshold,$group_leader,$flood_control_submit_secs,$flood_control_access_secs,$max_daily_upload_mb,$max_attachments_per_post,$max_avatar_width,$max_avatar_height,$max_post_length_comcode,$max_sig_length_comcode,$gift_points_base,$gift_points_per_day,$enquire_on_new_ips,$is_presented_at_install,$hidden,$order,$rank_image_pri_only,$open_membership,$is_private_club,$uniqify=false)
{
	$test=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_groups g LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON g.g_name=t.id WHERE '.db_string_equal_to('text_original',$name),'g.id');
	if ((!is_null($test)) && ($test!=$group_id))
	{
		if ($uniqify)
		{
			$name.='_'.uniqid('',true);
		} else
		{
			warn_exit(do_lang_tempcode('ALREADY_EXISTS',escape_html($name)));
		}
	}

	$_group_info=$GLOBALS['FORUM_DB']->query_select('f_groups',array('g_name','g_title','g_rank_image'),array('id'=>$group_id),'',1);
	if (!array_key_exists(0,$_group_info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$_name=$_group_info[0]['g_name'];
	$_title=$_group_info[0]['g_title'];

	$map=array();
	if (!is_null($name)) $map['g_name']=lang_remap($_name,$name,$GLOBALS['FORUM_DB']);
	if (!is_null($is_default)) $map['g_is_default']=$is_default;
	if (!is_null($is_presented_at_install)) $map['g_is_presented_at_install']=$is_presented_at_install;
	if (!is_null($is_super_admin)) $map['g_is_super_admin']=$is_super_admin;
	if (!is_null($is_super_moderator)) $map['g_is_super_moderator']=$is_super_moderator;
	$map['g_group_leader']=$group_leader;
	if (!is_null($title)) $map['g_title']=lang_remap($_title,$title,$GLOBALS['FORUM_DB']);
	$map['g_promotion_target']=$promotion_target;
	$map['g_promotion_threshold']=$promotion_threshold;
	if (!is_null($flood_control_submit_secs)) $map['g_flood_control_submit_secs']=$flood_control_submit_secs;
	if (!is_null($flood_control_access_secs)) $map['g_flood_control_access_secs']=$flood_control_access_secs;
	if (!is_null($max_daily_upload_mb)) $map['g_max_daily_upload_mb']=$max_daily_upload_mb;
	if (!is_null($max_attachments_per_post)) $map['g_max_attachments_per_post']=$max_attachments_per_post;
	if (!is_null($max_avatar_width)) $map['g_max_avatar_width']=$max_avatar_width;
	if (!is_null($max_avatar_height)) $map['g_max_avatar_height']=$max_avatar_height;
	if (!is_null($max_post_length_comcode)) $map['g_max_post_length_comcode']=$max_post_length_comcode;
	if (!is_null($max_sig_length_comcode)) $map['g_max_sig_length_comcode']=$max_sig_length_comcode;
	if (!is_null($gift_points_base)) $map['g_gift_points_base']=$gift_points_base;
	if (!is_null($gift_points_per_day)) $map['g_gift_points_per_day']=$gift_points_per_day;
	if (!is_null($enquire_on_new_ips)) $map['g_enquire_on_new_ips']=$enquire_on_new_ips;
	if (!is_null($rank_image)) $map['g_rank_image']=$rank_image;
	if (!is_null($hidden)) $map['g_hidden']=$hidden;
	if (!is_null($order)) $map['g_order']=$order;
	if (!is_null($rank_image_pri_only)) $map['g_rank_image_pri_only']=$rank_image_pri_only;
	if (!is_null($open_membership)) $map['g_open_membership']=$open_membership;
	if (!is_null($is_private_club)) $map['g_is_private_club']=$is_private_club;

	$GLOBALS['FORUM_DB']->query_update('f_groups',$map,array('id'=>$group_id),'',1);

	require_code('urls2');
	suggest_new_idmoniker_for('groups','view',strval($group_id),'',$name);

	require_code('themes2');
	tidy_theme_img_code($rank_image,$_group_info[0]['g_rank_image'],'f_groups','g_rank_image',$GLOBALS['FORUM_DB']);

	log_it('EDIT_GROUP',strval($group_id),$name);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('group',strval($group_id));
	}
}

/**
 * Delete a usergroup.
 *
 * @param  AUTO_LINK		The ID of the usergroup to delete.
 * @param  ?GROUP			The usergroup to move primary members to (NULL: main members).
 */
function ocf_delete_group($group_id,$target_group=NULL)
{
	$orig_target_group=$target_group;
	require_code('ocf_groups');
	if (is_null($target_group)) $target_group=get_first_default_group();

	if (($group_id==db_get_first_id()+0) || ($group_id==db_get_first_id()+1) || ($group_id==db_get_first_id()+8))
		fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));

	$_group_info=$GLOBALS['FORUM_DB']->query_select('f_groups',array('g_name','g_title','g_rank_image'),array('id'=>$group_id),'',1);
	if (!array_key_exists(0,$_group_info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$_name=$_group_info[0]['g_name'];
	$_title=$_group_info[0]['g_title'];
	$name=get_translated_text($_name,$GLOBALS['FORUM_DB']);
	delete_lang($_name,$GLOBALS['FORUM_DB']);
	delete_lang($_title,$GLOBALS['FORUM_DB']);

	$GLOBALS['FORUM_DB']->query_update('f_groups',array('g_promotion_target'=>NULL),array('g_promotion_target'=>$group_id));
	$GLOBALS['FORUM_DB']->query_update('f_members',array('m_primary_group'=>$target_group),array('m_primary_group'=>$group_id));
	if (!is_null($orig_target_group))
		$GLOBALS['FORUM_DB']->query_update('f_group_members',array('gm_group_id'=>$target_group),array('gm_group_id'=>$group_id),'',NULL,NULL,false,true);
	$GLOBALS['FORUM_DB']->query_delete('f_group_members',array('gm_group_id'=>$group_id));
	$GLOBALS['FORUM_DB']->query_delete('f_groups',array('id'=>$group_id),'',1);
	// No need to delete ocPortal permission stuff, as it could be on any MSN site, and ocPortal is coded with a tolerance due to the forum driver system. However, to be tidy...
	$GLOBALS['SITE_DB']->query_delete('group_privileges',array('group_id'=>$group_id));
	$GLOBALS['SITE_DB']->query_delete('group_zone_access',array('group_id'=>$group_id));
	$GLOBALS['SITE_DB']->query_delete('group_category_access',array('group_id'=>$group_id));
	$GLOBALS['SITE_DB']->query_delete('group_page_access',array('group_id'=>$group_id));
	if (addon_installed('ecommerce'))
		$GLOBALS['FORUM_DB']->query_delete('f_usergroup_subs',array('s_group_id'=>$group_id));

	require_code('themes2');
	tidy_theme_img_code(NULL,$_group_info[0]['g_rank_image'],'f_groups','g_rank_image',$GLOBALS['FORUM_DB']);

	log_it('DELETE_GROUP',strval($group_id),$name);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		expunge_resourcefs_moniker('group',strval($group_id));
	}
}

/**
 * Mark a member as applying to be in a certain, and inform the leader.
 *
 * @param  GROUP		The usergroup to apply to.
 * @param  ?MEMBER	The member applying (NULL: current member).
 */
function ocf_member_ask_join_group($group_id,$member_id=NULL)
{
	require_code('notifications');

	$group_info=$GLOBALS['FORUM_DB']->query_select('f_groups',array('g_name','g_group_leader'),array('id'=>$group_id),'',1);
	if (!array_key_exists(0,$group_info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));

	if (is_null($member_id)) $member_id=get_member();

	if (ocf_is_ldap_member($member_id)) return;

	$test=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_group_members','gm_validated',array('gm_member_id'=>$member_id,'gm_group_id'=>$group_id));
	if (!is_null($test))
	{
		if ($test==1) warn_exit(do_lang_tempcode('ALREADY_IN_GROUP'));
		warn_exit(do_lang_tempcode('ALREADY_APPLIED_FOR_GROUP'));
	}

	$validated=0;
	$in=$GLOBALS['OCF_DRIVER']->get_members_groups($member_id);
//	if (count($in)==1)
	{
		$test=$GLOBALS['FORUM_DB']->query_select_value('f_groups','g_is_presented_at_install',array('id'=>$group_id));
		if ($test==1) $validated=1;
	}

	$GLOBALS['FORUM_DB']->query_insert('f_group_members',array(
		'gm_group_id'=>$group_id,
		'gm_member_id'=>$member_id,
		'gm_validated'=>$validated
	));
	if ($validated==1)
	{
		$GLOBALS['FORUM_DB']->query_insert('f_group_join_log',array(
			'member_id'=>$member_id,
			'usergroup_id'=>$group_id,
			'join_time'=>time()
		));
	}

	if ($validated==0)
	{
		$group_name=get_translated_text($group_info[0]['g_name'],$GLOBALS['FORUM_DB']);
		$_url=build_url(array('page'=>'groups','type'=>'view','id'=>$group_id),get_module_zone('groups'),NULL,false,false,true);
		$url=$_url->evaluate();
		$their_username=$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_username');

		$leader_id=$group_info[0]['g_group_leader'];
		if (!is_null($leader_id))
		{
			$mail=do_lang('GROUP_JOIN_REQUEST_MAIL',comcode_escape($their_username),comcode_escape($group_name),array($url),get_lang($leader_id));
			$subject=do_lang('GROUP_JOIN_REQUEST_MAIL_SUBJECT',NULL,NULL,NULL,get_lang($leader_id));
			dispatch_notification('ocf_group_join_request',NULL,$subject,$mail,array($leader_id));
		} else
		{
			$mail=do_lang('GROUP_JOIN_REQUEST_MAIL',comcode_escape($their_username),comcode_escape($group_name),array($url),get_site_default_lang());
			$subject=do_lang('GROUP_JOIN_REQUEST_MAIL_SUBJECT',NULL,NULL,NULL,get_site_default_lang());
			dispatch_notification('ocf_group_join_request_staff',NULL,$subject,$mail);
		}
	}
}

/**
 * Remove a member from a certain usergroup.
 *
 * @param  GROUP		The usergroup to remove from.
 * @param  ?MEMBER	The member leaving (NULL: current member).
 */
function ocf_member_leave_group($group_id,$member_id=NULL)
{
	if (is_null($member_id)) $member_id=get_member();

	if (ocf_is_ldap_member($member_id)) return;

	$group_leader=$GLOBALS['FORUM_DB']->query_select_value('f_groups','g_group_leader',array('id'=>$group_id));
	if ($group_leader==$member_id) $GLOBALS['FORUM_DB']->query_update('f_groups',array('g_group_leader'=>NULL),array('id'=>$group_id),'',1);

	$GLOBALS['FORUM_DB']->query_delete('f_group_members',array('gm_group_id'=>$group_id,'gm_member_id'=>$member_id),'',1);
	
	$GLOBALS['FORUM_DB']->query_delete('f_group_join_log',array(
		'member_id'=>$member_id,
		'usergroup_id'=>$group_id,
	));
}

/**
 * Add a member to a certain usergroup.
 *
 * @param  MEMBER	The member.
 * @param  GROUP	The usergroup.
 * @param  BINARY	Whether the member is validated into the usergroup.
 */
function ocf_add_member_to_group($member_id,$id,$validated=1)
{
	if (ocf_is_ldap_member($member_id)) return;

	$test=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_groups','g_is_presented_at_install',array('id'=>$id));
	if (is_null($test)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));

	$GLOBALS['FORUM_DB']->query_insert('f_group_members',array(
		'gm_group_id'=>$id,
		'gm_member_id'=>$member_id,
		'gm_validated'=>$validated
	),false,true); // Allow failure, if member is already in (handy for importers)

	if (ocf_get_group_property($id,'hidden')==0)
	{
		require_code('notifications');
		$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id);
		$displayname=$GLOBALS['FORUM_DRIVER']->get_username($member_id,true);
		$group_name=ocf_get_group_name($id);
		$subject=do_lang('MJG_NOTIFICATION_MAIL_SUBJECT',get_site_name(),$username,$group_name);
		$group_url=build_url(array('page'=>'groups','type'=>'view','id'=>$id),get_module_zone('groups'),NULL,false,false,true);
		$mail=do_lang('MJG_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape($username),array(comcode_escape($group_name),$group_url->evaluate(),comcode_escape($displayname)));
		dispatch_notification('ocf_member_joined_group',strval($id),$subject,$mail);
	}

	if ($validated==1)
	{
		$GLOBALS['FORUM_DB']->query_insert('f_group_join_log',array(
			'member_id'=>$member_id,
			'usergroup_id'=>$id,
			'join_time'=>time()
		));
	}
}

/**
 * Set whether a member that has applied to be in a, may be in it, and inform them of the decision.
 *
 * @param  GROUP		The usergroup.
 * @param  MEMBER		The prospective member.
 * @param  boolean	Whether the member is being declined membership.
 * @param  string		The reason given for declining.
 */
function ocf_member_validate_into_group($group_id,$prospective_member_id,$decline=false,$reason='')
{
	if (ocf_is_ldap_member($prospective_member_id)) return;

	require_code('notifications');

	$GLOBALS['FORUM_DB']->query_delete('f_group_members',array('gm_member_id'=>$prospective_member_id,'gm_group_id'=>$group_id),'',1);

	$name=ocf_get_group_name($group_id);

	if (!$decline)
	{
		$GLOBALS['FORUM_DB']->query_insert('f_group_members',array(
			'gm_group_id'=>$group_id,
			'gm_member_id'=>$prospective_member_id,
			'gm_validated'=>1
		));

		$GLOBALS['FORUM_DB']->query_insert('f_group_join_log',array(
			'member_id'=>$prospective_member_id,
			'usergroup_id'=>$group_id,
			'join_time'=>time()
		));

		$mail=do_lang('GROUP_ACCEPTED_MAIL',get_site_name(),$name,NULL,get_lang($prospective_member_id));
		$subject=do_lang('GROUP_ACCEPTED_MAIL_SUBJECT',$name,NULL,NULL,get_lang($prospective_member_id));
	} else
	{
		if ($reason!='')
		{
			$mail=do_lang('GROUP_DECLINED_MAIL_REASON',comcode_escape(get_site_name()),comcode_escape($name),comcode_escape($reason),get_lang($prospective_member_id));
		} else
		{
			$mail=do_lang('GROUP_DECLINED_MAIL',comcode_escape(get_site_name()),comcode_escape($name),NULL,get_lang($prospective_member_id));
		}
		$subject=do_lang('GROUP_DECLINED_MAIL_SUBJECT',$name,NULL,NULL,get_lang($prospective_member_id));
	}

	dispatch_notification('ocf_group_declined',NULL,$subject,$mail,array($prospective_member_id));
}

/**
 * Copy permissions relating to one, to another.
 *
 * @param  GROUP	The that is having it's permissions replaced.
 * @param  GROUP	The that the permissions are being drawn from.
 */
function ocf_group_absorb_privileges_of($to,$from)
{
	_ocf_group_absorb_privileges_of($to,$from,'group_category_access');
	_ocf_group_absorb_privileges_of($to,$from,'group_zone_access');
	_ocf_group_absorb_privileges_of($to,$from,'group_page_access');
	_ocf_group_absorb_privileges_of($to,$from,'group_privileges');
}

/**
 * Helper function, for copy permissions relating to one, to another.
 *
 * @param  GROUP		The that is having it's permissions replaced.
 * @param  GROUP		The that the permissions are being drawn from.
 * @param  ID_TEXT	The table holding the permissions.
 * @param  ID_TEXT	The name of the field in the table that holds the ID.
 * @param  boolean	Whether the operation is being carried out over the OCF driver.
 */
function _ocf_group_absorb_privileges_of($to,$from,$table,$id='group_id',$ocf=false)
{
	if ($to==$from) fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));

	if ($ocf)
	{
		$GLOBALS['FORUM_DB']->query_delete($table,array($id=>$to));
		$rows=$GLOBALS['FORUM_DB']->query_select($table,array('*'),array($id=>$from));
		foreach ($rows as $row)
		{
			$row[$id]=$to;
			$GLOBALS['FORUM_DB']->query_insert($table,$row);
		}
	}
	else
	{
		$GLOBALS['SITE_DB']->query_delete($table,array($id=>$to));
		$rows=$GLOBALS['SITE_DB']->query_select($table,array('*'),array($id=>$from));
		foreach ($rows as $row)
		{
			$row[$id]=$to;
			$GLOBALS['SITE_DB']->query_insert($table,$row);
		}
	}
}


