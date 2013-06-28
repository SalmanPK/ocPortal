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
 * @package		calendar
 */

/**
 * Add a calendar event.
 *
 * @param  AUTO_LINK			The event type
 * @param  SHORT_TEXT		The recurrence code (set to 'none' for no recurrences: blank means infinite and will basically time-out ocPortal)
 * @param  ?integer			The number of recurrences (NULL: none/infinite)
 * @param  BINARY				Whether to segregate the comment-topics/rating/trackbacks per-recurrence
 * @param  SHORT_TEXT		The title of the event
 * @param  LONG_TEXT			The full text describing the event
 * @param  integer			The priority
 * @range  1 5
 * @param  BINARY				Whether it is a public event
 * @param  integer			The year the event starts at
 * @param  integer			The month the event starts at
 * @param  integer			The day the event starts at
 * @param  ID_TEXT			In-month specification type for start date
 * @set day_of_month day_of_month_backwards dow_of_month dow_of_month_backwards
 * @param  integer			The hour the event starts at
 * @param  integer			The minute the event starts at
 * @param  ?integer			The year the event ends at (NULL: not a multi day event)
 * @param  ?integer			The month the event ends at (NULL: not a multi day event)
 * @param  ?integer			The day the event ends at (NULL: not a multi day event)
 * @param  ID_TEXT			In-month specification type for end date
 * @set day_of_month day_of_month_backwards dow_of_month dow_of_month_backwards
 * @param  ?integer			The hour the event ends at (NULL: not a multi day event)
 * @param  ?integer			The minute the event ends at (NULL: not a multi day event)
 * @param  ?ID_TEXT			The timezone for the event (NULL: current user's timezone)
 * @param  BINARY				Whether the time should be presented in the viewer's own timezone
 * @param  BINARY				Whether the event has been validated
 * @param  BINARY				Whether the event may be rated
 * @param  SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY				Whether the event may be trackbacked
 * @param  LONG_TEXT			Hidden notes pertaining to the download
 * @param  ?MEMBER			The event submitter (NULL: current member)
 * @param  integer			The number of views so far
 * @param  ?TIME				The add time (NULL: now)
 * @param  ?TIME				The edit time (NULL: never)
 * @param  ?AUTO_LINK		Force an ID (NULL: don't force an ID)
 * @param  ?SHORT_TEXT		Meta keywords for this resource (NULL: do not edit) (blank: implicit)
 * @param  ?LONG_TEXT		Meta description for this resource (NULL: do not edit) (blank: implicit)
 * @return AUTO_LINK			The ID of the event
 */
function add_calendar_event($type,$recurrence,$recurrences,$seg_recurrences,$title,$content,$priority,$is_public,$start_year,$start_month,$start_day,$start_monthly_spec_type,$start_hour,$start_minute,$end_year=NULL,$end_month=NULL,$end_day=NULL,$end_monthly_spec_type='day_of_month',$end_hour=NULL,$end_minute=NULL,$timezone=NULL,$do_timezone_conv=1,$validated=1,$allow_rating=1,$allow_comments=1,$allow_trackbacks=1,$notes='',$submitter=NULL,$views=0,$add_time=NULL,$edit_time=NULL,$id=NULL,$meta_keywords='',$meta_description='')
{
	if (is_null($submitter)) $submitter=get_member();
	if (is_null($add_time)) $add_time=time();

	if (is_null($timezone)) $timezone=get_users_timezone();

	require_code('comcode_check');

	check_comcode($content,NULL,false,NULL,true);

	if (!addon_installed('unvalidated')) $validated=1;
	$map=array(
		'e_submitter'=>$submitter,
		'e_views'=>$views,
		'e_title'=>insert_lang($title,2),
		'e_content'=>0,
		'e_add_date'=>$add_time,
		'e_edit_date'=>$edit_time,
		'e_recurrence'=>$recurrence,
		'e_recurrences'=>$recurrences,
		'e_seg_recurrences'=>$seg_recurrences,
		'e_start_year'=>$start_year,
		'e_start_month'=>$start_month,
		'e_start_day'=>$start_day,
		'e_start_monthly_spec_type'=>$start_monthly_spec_type,
		'e_start_hour'=>$start_hour,
		'e_start_minute'=>$start_minute,
		'e_end_year'=>$end_year,
		'e_end_month'=>$end_month,
		'e_end_day'=>$end_day,
		'e_end_monthly_spec_type'=>$end_monthly_spec_type,
		'e_end_hour'=>$end_hour,
		'e_end_minute'=>$end_minute,
		'e_timezone'=>$timezone,
		'e_do_timezone_conv'=>$do_timezone_conv,
		'e_is_public'=>$is_public,
		'e_priority'=>$priority,
		'e_type'=>$type,
		'validated'=>$validated,
		'allow_rating'=>$allow_rating,
		'allow_comments'=>$allow_comments,
		'allow_trackbacks'=>$allow_trackbacks,
		'notes'=>$notes
	);
	if (!is_null($id)) $map['id']=$id;
	$id=$GLOBALS['SITE_DB']->query_insert('calendar_events',$map,true);

	require_code('attachments2');
	$GLOBALS['SITE_DB']->query_update('calendar_events',array('e_content'=>insert_lang_comcode_attachments(3,$content,'calendar',strval($id))),array('id'=>$id),'',1);

	require_code('seo2');
	if (($meta_keywords=='') && ($meta_description==''))
	{
		seo_meta_set_for_implicit('event',strval($id),array($title,$content),$content);
	} else
	{
		seo_meta_set_for_explicit('event',strval($id),$meta_keywords,$meta_description);
	}

	decache('side_calendar');

	if ($validated==1)
	{
		require_lang('calendar');
		require_code('notifications');
		$subject=do_lang('CALENDAR_EVENT_NOTIFICATION_MAIL_SUBJECT',get_site_name(),strip_comcode($title));
		$self_url=build_url(array('page'=>'calendar','type'=>'view','id'=>$id),get_module_zone('calendar'),NULL,false,false,true);
		$mail=do_lang('CALENDAR_EVENT_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape($title),array($self_url->evaluate()));
		dispatch_notification('calendar_event',strval($type),$subject,$mail);
	}

	log_it('ADD_CALENDAR_EVENT',strval($id),$title);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('event',strval($id),NULL,NULL,true);
	}

	return $id;
}

/**
 * Edit a calendar event.
 *
 * @param  AUTO_LINK			The ID of the event
 * @param  ?AUTO_LINK		The event type (NULL: default)
 * @param  SHORT_TEXT		The recurrence code
 * @param  ?integer			The number of recurrences (NULL: none/infinite)
 * @param  BINARY				Whether to segregate the comment-topics/rating/trackbacks per-recurrence
 * @param  SHORT_TEXT		The title of the event
 * @param  LONG_TEXT			The full text describing the event
 * @param  integer			The priority
 * @range  1 5
 * @param  BINARY				Whether it is a public event
 * @param  integer			The year the event starts at
 * @param  integer			The month the event starts at
 * @param  integer			The day the event starts at
 * @param  ID_TEXT			In-month specification type for start date
 * @set day_of_month day_of_month_backwards dow_of_month dow_of_month_backwards
 * @param  integer			The hour the event starts at
 * @param  integer			The minute the event starts at
 * @param  ?integer			The year the event ends at (NULL: not a multi day event)
 * @param  ?integer			The month the event ends at (NULL: not a multi day event)
 * @param  ?integer			The day the event ends at (NULL: not a multi day event)
 * @param  ID_TEXT			In-month specification type for end date
 * @set day_of_month day_of_month_backwards dow_of_month dow_of_month_backwards
 * @param  ?integer			The hour the event ends at (NULL: not a multi day event)
 * @param  ?integer			The minute the event ends at (NULL: not a multi day event)
 * @param  ?ID_TEXT			The timezone for the event (NULL: current user's timezone)
 * @param  BINARY				Whether the time should be presented in the viewer's own timezone
 * @param  SHORT_TEXT		Meta keywords
 * @param  LONG_TEXT			Meta description
 * @param  BINARY				Whether the download has been validated
 * @param  BINARY				Whether the download may be rated
 * @param  SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY				Whether the download may be trackbacked
 * @param  LONG_TEXT			Hidden notes pertaining to the download
 * @param  ?TIME				Edit time (NULL: either means current time, or if $null_is_literal, means reset to to NULL)
 * @param  ?TIME				Add time (NULL: do not change)
 * @param  ?integer			Number of views (NULL: do not change)
 * @param  ?MEMBER			Submitter (NULL: do not change)
 * @param  boolean			Determines whether some NULLs passed mean 'use a default' or literally mean 'set to NULL'
 */
function edit_calendar_event($id,$type,$recurrence,$recurrences,$seg_recurrences,$title,$content,$priority,$is_public,$start_year,$start_month,$start_day,$start_monthly_spec_type,$start_hour,$start_minute,$end_year,$end_month,$end_day,$end_monthly_spec_type,$end_hour,$end_minute,$timezone,$do_timezone_conv,$meta_keywords,$meta_description,$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$edit_time=NULL,$add_time=NULL,$views=NULL,$submitter=NULL,$null_is_literal=false)
{
	if (is_null($edit_time)) $edit_time=$null_is_literal?NULL:time();

	$myrows=$GLOBALS['SITE_DB']->query_select('calendar_events',array('e_title','e_content','e_submitter'),array('id'=>$id),'',1);
	$myrow=$myrows[0];

	require_code('urls2');
	suggest_new_idmoniker_for('calendar','view',strval($id),'',$title);

	require_code('seo2');
	seo_meta_set_for_explicit('event',strval($id),$meta_keywords,$meta_description);

	require_code('attachments2');
	require_code('attachments3');

	if (!addon_installed('unvalidated')) $validated=1;

	require_code('submit');
	$just_validated=(!content_validated('event',strval($id))) && ($validated==1);
	if ($just_validated)
	{
		send_content_validated_notification('event',strval($id));
	}

	$update_map=array(
		'e_title'=>lang_remap($myrow['e_title'],$title),
		'e_content'=>update_lang_comcode_attachments($myrow['e_content'],$content,'calendar',strval($id),NULL,false,$myrow['e_submitter']),
		'e_recurrence'=>$recurrence,
		'e_recurrences'=>$recurrences,
		'e_seg_recurrences'=>$seg_recurrences,
		'e_start_year'=>$start_year,
		'e_start_month'=>$start_month,
		'e_start_day'=>$start_day,
		'e_start_monthly_spec_type'=>$start_monthly_spec_type,
		'e_start_hour'=>$start_hour,
		'e_start_minute'=>$start_minute,
		'e_end_year'=>$end_year,
		'e_end_month'=>$end_month,
		'e_end_day'=>$end_day,
		'e_end_monthly_spec_type'=>$end_monthly_spec_type,
		'e_end_hour'=>$end_hour,
		'e_end_minute'=>$end_minute,
		'e_timezone'=>$timezone,
		'e_do_timezone_conv'=>$do_timezone_conv,
		'e_is_public'=>$is_public,
		'e_priority'=>$priority,
		'e_type'=>$type,
		'validated'=>$validated,
		'allow_rating'=>$allow_rating,
		'allow_comments'=>$allow_comments,
		'allow_trackbacks'=>$allow_trackbacks,
		'notes'=>$notes
	);

	$update_map['e_edit_date']=$edit_time;
	if (!is_null($add_time))
		$update_map['e_add_date']=$add_time;
	if (!is_null($views))
		$update_map['e_views']=$views;
	if (!is_null($submitter))
		$update_map['e_submitter']=$submitter;

	$GLOBALS['SITE_DB']->query_update('calendar_events',$update_map,array('id'=>$id),'',1);

	$self_url=build_url(array('page'=>'calendar','type'=>'view','id'=>$id),get_module_zone('calendar'),NULL,false,false,true);

	if ($just_validated)
	{
		require_lang('calendar');
		require_code('notifications');
		$subject=do_lang('CALENDAR_EVENT_NOTIFICATION_MAIL_SUBJECT',get_site_name(),strip_comcode($title));
		$mail=do_lang('CALENDAR_EVENT_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape($title),array($self_url->evaluate()));
		dispatch_notification('calendar_event',strval($type),$subject,$mail);
	}

	decache('side_calendar');

	require_code('feedback');
	update_spacer_post($allow_comments!=0,'events',strval($id),$self_url,$title,get_value('comment_forum__calendar'));

	log_it('EDIT_CALENDAR_EVENT',strval($id),$title);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('event',strval($id));
	}
}

/**
 * Delete a calendar event.
 *
 * @param  AUTO_LINK		The ID of the event
 */
function delete_calendar_event($id)
{
	$myrows=$GLOBALS['SITE_DB']->query_select('calendar_events',array('e_title','e_content'),array('id'=>$id),'',1);
	$myrow=$myrows[0];
	$e_title=get_translated_text($myrow['e_title']);

	$GLOBALS['SITE_DB']->query_delete('calendar_events',array('id'=>$id),'',1);

	$GLOBALS['SITE_DB']->query_delete('calendar_jobs',array('j_event_id'=>$id));
	$GLOBALS['SITE_DB']->query_delete('calendar_reminders',array('e_id'=>$id));

	require_code('seo2');
	seo_meta_erase_storage('event',strval($id));

	$GLOBALS['SITE_DB']->query_delete('rating',array('rating_for_type'=>'events','rating_for_id'=>$id));
	$GLOBALS['SITE_DB']->query_delete('trackbacks',array('trackback_for_type'=>'events','trackback_for_id'=>$id));
	require_code('notifications');
	delete_all_notifications_on('comment_posted','events_'.strval($id));

	delete_lang($myrow['e_title']);
	require_code('attachments2');
	require_code('attachments3');
	if (!is_null($myrow['e_content']))
		delete_lang_comcode_attachments($myrow['e_content'],'e_content',strval($id));

	decache('side_calendar');

	log_it('DELETE_CALENDAR_EVENT',strval($id),$e_title);
	
	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		expunge_resourcefs_moniker('event',strval($id));
	}
}

/**
 * Add a calendar event type.
 *
 * @param  SHORT_TEXT		The title of the event type
 * @param  ID_TEXT			The theme image code
 * @param  URLPATH			URL to external feed to associate with this event type
 * @return AUTO_LINK			The ID of the event type
 */
function add_event_type($title,$logo,$external_feed='')
{
	$id=$GLOBALS['SITE_DB']->query_insert('calendar_types',array(
		't_title'=>insert_lang($title,2),
		't_logo'=>$logo,
		't_external_feed'=>$external_feed,
	),true);

	log_it('ADD_EVENT_TYPE',strval($id),$title);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('calendar_type',strval($id),NULL,NULL,true);
	}

	return $id;
}

/**
 * Edit a calendar event type.
 *
 * @param  AUTO_LINK			The ID of the event type
 * @param  SHORT_TEXT		The title of the event type
 * @param  ID_TEXT			The theme image code
 * @param  URLPATH			URL to external feed to associate with this event type
 */
function edit_event_type($id,$title,$logo,$external_feed)
{
	$myrows=$GLOBALS['SITE_DB']->query_select('calendar_types',array('t_title','t_logo'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$myrows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$myrows[0];

	require_code('urls2');
	suggest_new_idmoniker_for('calendar','misc',strval($id),'',$title);

	$GLOBALS['SITE_DB']->query_update('calendar_types',array(
		't_title'=>lang_remap($myrow['t_title'],$title),
		't_logo'=>$logo,
		't_external_feed'=>$external_feed,
	),array('id'=>$id),'',1);

	require_code('themes2');
	tidy_theme_img_code($logo,$myrow['t_logo'],'calendar_types','t_logo');

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('calendar_type',strval($id));
	}

	log_it('EDIT_EVENT_TYPE',strval($id),$title);
}

/**
 * Delete a calendar event type.
 *
 * @param  AUTO_LINK		The ID of the event type
 */
function delete_event_type($id)
{
	$myrows=$GLOBALS['SITE_DB']->query_select('calendar_types',array('t_title','t_logo'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$myrows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$myrows[0];

	$lowest=$GLOBALS['SITE_DB']->query_value_if_there('SELECT MIN(id) FROM '.get_table_prefix().'calendar_types WHERE id<>'.strval($id).' AND id<>'.strval(db_get_first_id()));
	if (is_null($lowest)) warn_exit(do_lang_tempcode('NO_DELETE_LAST_CATEGORY'));
	$GLOBALS['SITE_DB']->query_update('calendar_events',array('e_type'=>$lowest),array('e_type'=>$id));

	$GLOBALS['SITE_DB']->query_delete('calendar_types',array('id'=>$id),'',1);
	$GLOBALS['SITE_DB']->query_delete('calendar_interests',array('t_type'=>$id));

	delete_lang($myrow['t_title']);

	$GLOBALS['SITE_DB']->query_delete('group_category_access',array('module_the_name'=>'calendar','category_name'=>strval($id)));

	require_code('themes2');
	tidy_theme_img_code(NULL,$myrow['t_logo'],'calendar_types','t_logo');

	log_it('DELETE_EVENT_TYPE',strval($id),get_translated_text($myrow['t_title']));
	
	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		expunge_resourcefs_moniker('calendar_type',strval($id));
	}
}


