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
 * @package		core_ocf
 */

/**
 * Edit a multi moderation.
 *
 * @param  AUTO_LINK		The ID of the multi moderation we are editing.
 * @param  SHORT_TEXT	The name of the multi moderation.
 * @param  LONG_TEXT		The default post text to add when applying (may be blank).
 * @param  ?AUTO_LINK	The forum to move the topic when applying (NULL: do not move).
 * @param  ?BINARY		The pin state after applying (NULL: unchanged).
 * @param  ?BINARY		The sink state after applying (NULL: unchanged).
 * @param  ?BINARY		The open state after applying (NULL: unchanged).
 * @param  SHORT_TEXT 	The forum multi code for where this multi moderation may be applied.
 * @param  SHORT_TEXT 	The title suffix.
 */
function ocf_edit_multi_moderation($id,$name,$post_text,$move_to,$pin_state,$sink_state,$open_state,$forum_multi_code,$title_suffix)
{
	$_name=$GLOBALS['FORUM_DB']->query_select_value('f_multi_moderations','mm_name',array('id'=>$id));

	$map=array(
		'mm_post_text'=>$post_text,
		'mm_move_to'=>$move_to,
		'mm_pin_state'=>$pin_state,
		'mm_sink_state'=>$sink_state,
		'mm_open_state'=>$open_state,
		'mm_forum_multi_code'=>$forum_multi_code,
		'mm_title_suffix'=>$title_suffix,
	);
	$map+=lang_remap('mm_name',$_name,$name,$GLOBALS['FORUM_DB']);
	$GLOBALS['FORUM_DB']->query_update('f_multi_moderations',$map,array('id'=>$id),'',1);

	log_it('EDIT_MULTI_MODERATION',strval($id),$name);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('multi_moderation',strval($id));
	}
}

/**
 * Delete a multi moderation.
 *
 * @param  AUTO_LINK  The ID of the multi moderation we are deleting.
 */
function ocf_delete_multi_moderation($id)
{
	$_name=$GLOBALS['FORUM_DB']->query_select_value('f_multi_moderations','mm_name',array('id'=>$id));
	$name=get_translated_text($_name,$GLOBALS['FORUM_DB']);
	$GLOBALS['FORUM_DB']->query_delete('f_multi_moderations',array('id'=>$id),'',1);
	delete_lang($_name,$GLOBALS['FORUM_DB']);

	log_it('DELETE_MULTI_MODERATION',strval($id),$name);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		expunge_resourcefs_moniker('multi_moderation',strval($id));
	}
}

/**
 * Perform a multi moderation.
 *
 * @param  AUTO_LINK		The ID of the multi moderation we are performing.
 * @param  AUTO_LINK		The ID of the topic we are performing the multi moderation on.
 * @param  LONG_TEXT		The reason for performing the multi moderation (may be blank).
 * @param  LONG_TEXT		The post text for a post to be added to the topic (blank: do not add a post).
 * @param  BINARY			Whether the post is marked emphasised.
 * @param  BINARY			Whether to skip showing the posters signature in the post.
 */
function ocf_perform_multi_moderation($id,$topic_id,$reason,$post_text='',$is_emphasised=1,$skip_sig=0)
{
	$topic_details=$GLOBALS['FORUM_DB']->query_select('f_topics',array('t_forum_id','t_cache_first_title','t_cache_first_post_id'),array('id'=>$topic_id),'',1);
	if (!array_key_exists(0,$topic_details)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$from=$topic_details[0]['t_forum_id'];
	if (!ocf_may_perform_multi_moderation($from)) access_denied('I_ERROR');

	$mm=$GLOBALS['FORUM_DB']->query_select('f_multi_moderations',array('*'),array('id'=>$id));
	if (!array_key_exists(0,$mm)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));

	require_code('ocfiltering');
	$idlist=ocfilter_to_idlist_using_db($mm[0]['mm_forum_multi_code'],'id','f_forums','f_forums','f_parent_forum','f_parent_forum','id',true,true,$GLOBALS['FORUM_DB']);
	if (!in_array($from,$idlist)) warn_exit(do_lang_tempcode('MM_APPLY_TWICE'));

	$pin_state=$mm[0]['mm_pin_state'];
	$open_state=$mm[0]['mm_open_state'];
	$sink_state=$mm[0]['mm_sink_state'];
	$move_to=$mm[0]['mm_move_to'];
	$title_suffix=$mm[0]['mm_title_suffix'];
	//$post_text=$mm[0]['mm_post_text']; We'll allow user to specify the post_text, with this as a default
	$update_array=array();
	if (!is_null($pin_state)) $update_array['t_pinned']=$pin_state;
	if (!is_null($sink_state)) $update_array['t_sunk']=$sink_state;
	if (!is_null($open_state)) $update_array['t_is_open']=$open_state;
	if ($title_suffix!='')
	{
		$new_title=$topic_details[0]['t_cache_first_title'].' ['.$title_suffix.']';
		$update_array['t_cache_first_title']=$new_title;
		$GLOBALS['FORUM_DB']->query_update('f_posts',array('p_title'=>$new_title),array('id'=>$topic_details[0]['t_cache_first_post_id']),'',1);
	}

	if (count($update_array)!=0)
		$GLOBALS['FORUM_DB']->query_update('f_topics',$update_array,array('id'=>$topic_id),'',1);

	if (!is_null($move_to))
	{
		require_code('ocf_topics_action');
		require_code('ocf_topics_action2');
		ocf_move_topics($from,$move_to,array($topic_id));
	}

	if ($post_text!='')
	{
		require_code('ocf_posts_action');
		require_code('ocf_posts_action2');
		require_code('ocf_topics_action');
		require_code('ocf_topics_action2');
		ocf_make_post($topic_id,'',$post_text,$skip_sig,false,1,$is_emphasised);

		$forum_id=is_null($move_to)?$from:$move_to;
		handle_topic_ticket_reply($forum_id,$topic_id,$topic_details[0]['t_cache_first_title'],$post_text);
	}

	require_lang('ocf_multi_moderations');
	require_code('ocf_general_action2');
	ocf_mod_log_it('PERFORM_MULTI_MODERATION',strval($id),strval($topic_id),$reason);
}

/**
 * Script for loading presets from saved warnings.
 */
function warnings_script()
{
	if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();

	require_lang('ocf_warnings');

	if (!ocf_may_warn_members())
		access_denied('PRIVILEGE','warn_members');

	$type=get_param('type');

	if ($type=='delete') // Delete a saved warning
	{
		$_title=post_param('title');
		$GLOBALS['FORUM_DB']->query_delete('f_saved_warnings',array('s_title'=>$_title),'',1);
		$content=paragraph(do_lang_tempcode('SUCCESS'));
		$echo=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'dc97492788a5049e697a296ca10a0390','TITLE'=>do_lang_tempcode('DELETE_SAVED_WARNING'),'POPUP'=>true,'CONTENT'=>$content));
		$echo->evaluate_echo();
		return;
	}

	// Show list of saved warnings
	// ---------------------------

	$content=new ocp_tempcode();
	$rows=$GLOBALS['FORUM_DB']->query_select('f_saved_warnings',array('*'),NULL,'ORDER BY s_title');
	$keep=symbol_tempcode('KEEP');
	$url=find_script('warnings').'?type=delete'.$keep->evaluate();
	foreach ($rows as $myrow)
	{
		$delete_link=hyperlink($url,do_lang_tempcode('DELETE'),false,false,'',NULL,form_input_hidden('title',$myrow['s_title']));
		$content->attach(do_template('OCF_SAVED_WARNING',array('_GUID'=>'537a5e28bfdc3f2d2cb6c06b0a939b51','MESSAGE'=>$myrow['s_message'],'EXPLANATION'=>$myrow['s_explanation'],'TITLE'=>$myrow['s_title'],'DELETE_LINK'=>$delete_link)));
	}
	if ($content->is_empty()) $content=paragraph(do_lang_tempcode('NO_ENTRIES'),'rfdsfsdf3t45');

	$echo=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'90c86490760cee23a8d5b8a5d14122e9','TITLE'=>do_lang_tempcode('CHOOSE_SAVED_WARNING'),'POPUP'=>true,'CONTENT'=>$content));
	$echo->evaluate_echo();
}

/**
 * Add a formal warning.
 *
 * @param  MEMBER			The member being warned.
 * @param  LONG_TEXT		An explanation for why the member is being warned.
 * @param  ?MEMBER		The member doing the warning (NULL: current member).
 * @param  ?TIME			The time of the warning (NULL: now).
 * @param  BINARY			Whether this counts as a warning
 * @param  ?AUTO_LINK	The topic being silenced from (NULL: none)
 * @param  ?AUTO_LINK	The forum being silenced from (NULL: none)
 * @param  integer		Number of extra days for probation
 * @param  IP				The IP address being banned (blank: none)
 * @param  integer		The points being charged
 * @param  BINARY			Whether the member is being banned
 * @param  ?GROUP			The usergroup being changed from (NULL: no change)
 * @return AUTO_LINK		The ID of the newly created warning.
 */
function ocf_make_warning($member_id,$explanation,$by=NULL,$time=NULL,$is_warning=1,$silence_from_topic=NULL,$silence_from_forum=NULL,$probation=0,$banned_ip='',$charged_points=0,$banned_member=0,$changed_usergroup_from=NULL)
{
	if ((is_null($time)) && (!ocf_may_warn_members())) access_denied('PRIVILEGE','warn_members');

	if (is_null($time)) $time=time();
	if (is_null($by)) $by=get_member();

	if ($is_warning==1)
	{
		$GLOBALS['FORUM_DB']->query('UPDATE '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members SET m_cache_warnings=(m_cache_warnings+1) WHERE id='.strval($member_id),1);
	}

	return $GLOBALS['FORUM_DB']->query_insert('f_warnings',array(
		'w_member_id'=>$member_id,
		'w_time'=>$time,
		'w_explanation'=>$explanation,
		'w_by'=>$by,
		'w_is_warning'=>$is_warning,
		'p_silence_from_topic'=>$silence_from_topic,
		'p_silence_from_forum'=>$silence_from_forum,
		'p_probation'=>$probation,
		'p_banned_ip'=>$banned_ip,
		'p_charged_points'=>$charged_points,
		'p_banned_member'=>$banned_member,
		'p_changed_usergroup_from'=>$changed_usergroup_from,
	),true);
}

/**
 * Edit a formal warning.
 *
 * @param  AUTO_LINK		The ID of the formal warning we are editing.
 * @param  LONG_TEXT		An explanation for why the member is being warned.
 * @param  BINARY			Whether this counts as a warning
 */
function ocf_edit_warning($warning_id,$explanation,$is_warning=1)
{
	if (!ocf_may_warn_members()) access_denied('PRIVILEGE','warn_members');

   $member_id=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_warnings','w_member_id',array('id'=>$warning_id));
   if (is_null($member_id)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));

	$GLOBALS['FORUM_DB']->query_update('f_warnings',array('w_explanation'=>$explanation,'w_is_warning'=>$is_warning),array('id'=>$warning_id),'',1);

	$member_id=$GLOBALS['FORUM_DB']->query_select_value('f_warnings','w_member_id',array('id'=>$warning_id));
	$num_warnings=$GLOBALS['FORUM_DB']->query_select_value('f_warnings','COUNT(*)',array('w_is_warning'=>1,'w_member_id'=>$member_id));

	$GLOBALS['FORUM_DB']->query_update('f_members',array('m_cache_warnings'=>$num_warnings),array('id'=>$member_id),'',1);
}

/**
 * Delete a formal warning.
 *
 * @param  AUTO_LINK  The ID of the formal warning we are deleting.
 */
function ocf_delete_warning($warning_id)
{
	if (!ocf_may_warn_members()) access_denied('PRIVILEGE','warn_members');

	$member_id=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_warnings','w_member_id',array('id'=>$warning_id));
	if (is_null($member_id)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));

	$GLOBALS['FORUM_DB']->query('UPDATE '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members SET m_cache_warnings=(m_cache_warnings-1) WHERE id='.strval($member_id),1);

	$GLOBALS['FORUM_DB']->query_delete('f_warnings',array('id'=>$warning_id),'',1);
}


