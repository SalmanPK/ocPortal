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
 * @package		core
 */

/**
 * Save a file of merged web resources.
 *
 * @param  array				Resources (map of keys to 1), passed by reference as we alter it
 * @param  ID_TEXT			Resource type
 * @set .css .js
 * @param  PATH				Write path
 * @return boolean			If the merge happened
 */
function _save_web_resource_merging($resources,$type,$write_path)
{
	// Create merged resource...

	$data='';
	$good_to_go=true;
	$all_strict=true;
	foreach ($resources as $resource)
	{
		if ($resource=='no_cache') continue;

		if ($type=='.js')
		{
			$merge_from=javascript_enforce($resource);
		} else // .css
		{
			$merge_from=css_enforce($resource);
		}
		if ($merge_from!='')
		{
			if (is_file($merge_from))
			{
				$extra_data=unixify_line_format(file_get_contents($merge_from)).chr(10).chr(10);
				$data.=$extra_data;
				if (strpos($extra_data,'"use strict";')===false) $all_strict=false;
			} else // race condition
			{
				$good_to_go=false;
				break;
			}
		}
	}

	if ($good_to_go)
	{
		if (!$all_strict)
		{
			$data=str_replace('"use strict";','',$data);
		}

		$myfile=@fopen($write_path,'wb') OR intelligent_write_error($write_path); // Intentionally 'wb' to stop line ending conversions on Windows
		fwrite($myfile,$data);
		fclose($myfile);
		fix_permissions($write_path,0777);
		sync_file($write_path);
	}

	return $good_to_go;
}

/**
 * Take a Tempcode object and run some hackerish code to make it XHTML-strict.
 *
 * @param  object			Tempcode object
 * @return object			Tempcode object (no longer cache safe)
 */
function make_xhtml_strict($global)
{
	$_global=$global->evaluate();
	$_global=str_replace(
		'<!DOCTYPE html>',
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		$_global);
	$_global=preg_replace('#(<a\s[^>]*)onclick="([^"]*)"(\s[^>]*)target="_blank"#','${1}onclick="this.target=\'_blank\'; ${2}"${3}',$_global);
	$_global=preg_replace('#(<a\s[^>]*)target="_blank"(\s[^>]*)onclick="([^"]*)"#','${1}onclick="this.target=\'_blank\'; ${3}"${2}',$_global);
	$_global=preg_replace('#(<a\s[^>]*)target="_blank"#','${1}onclick="this.target=\'_blank\';"',$_global);
	$_global=preg_replace('#(<form\s[^>]*)onsubmit="([^"]*)"(\s[^>]*)target="_blank"#','${1}onsubmit="this.target=\'_blank\'; ${2}"${3}',$_global);
	$_global=preg_replace('#(<form\s[^>]*)target="_blank"(\s[^>]*)onsubmit="([^"]*)"#','${1}onsubmit="this.target=\'_blank\'; ${3}"${2}',$_global);
	$_global=preg_replace('#(<form\s[^>]*)target="_blank"#','${1}onsubmit="this.target=\'_blank\';"',$_global);
	$_global=preg_replace('#(<(a|form)\s[^>]*)target="[^"]*"#','${1}',$_global);
	return make_string_tempcode($_global);
}

/**
 * Find the country an IP address long is located in
 *
 * @param  ?IP				The IP to geolocate (NULL: current user's IP)
 * @return ?string		The country initials (NULL: unknown)
 */
function geolocate_ip($ip=NULL)
{
	if (is_null($ip)) $ip=get_ip_address();

	if (!addon_installed('stats')) return NULL;

	$long_ip=ip2long($ip);
	if ($long_ip===false) return NULL; // No IP6 support

	$query='SELECT * FROM '.get_table_prefix().'ip_country WHERE begin_num<='.sprintf('%u',$long_ip).' AND end_num>='.sprintf('%u',$long_ip);
	$results=$GLOBALS['SITE_DB']->query($query);

	if (!array_key_exists(0,$results)) return NULL;
	elseif (!is_null($results[0]['country'])) return $results[0]['country'];
	else return NULL;
}

/**
 * Get links and details related to a member.
 *
 * @param  MEMBER				A member ID
 * @return array				A tuple: links (Tempcode), details (Tempcode), number of unread inline personal posts or private topics
 */
function member_personal_links_and_details($member_id)
{
	$details=new ocp_tempcode();
	$links=new ocp_tempcode();

	if (get_forum_type()!='none')
	{
		// Post count
		if ((!has_no_forum()) && (get_option('forum_show_personal_stats_posts')=='1'))
		{
			$details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'371dfee46e8c40b1b109e0350055f8cc','KEY'=>do_lang_tempcode('COUNT_POSTSCOUNT'),'VALUE'=>integer_format($GLOBALS['FORUM_DRIVER']->get_post_count($member_id)))));
		}
		// Topic count
		if ((!has_no_forum()) && (get_option('forum_show_personal_stats_topics')=='1'))
		{
			$details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'2dd2a2d30c4ea7144c74ab058239fb23','KEY'=>do_lang_tempcode('COUNT_TOPICSCOUNT'),'VALUE'=>integer_format($GLOBALS['FORUM_DRIVER']->get_topic_count($member_id)))));
		}

		// Member profile view link
		if (get_option('ocf_show_profile_link')=='1')
		{
			$url=$GLOBALS['FORUM_DRIVER']->member_profile_url($member_id,true,true);
			$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK',array('_GUID'=>'2c8648c953c802a9de41c3adeef0e97f','NAME'=>do_lang_tempcode('MY_PROFILE'),'URL'=>$url,'REL'=>'me')));
		}
	}

	// Point count
	if (addon_installed('points'))
	{
		require_lang('points');
		require_code('points');
		if (get_option('points_show_personal_stats_points_left')=='1') $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'6241e58e30457576735f3a2618fd7fff','KEY'=>do_lang_tempcode('COUNT_POINTS_LEFT'),'VALUE'=>integer_format(available_points($member_id)))));
		if (get_option('points_show_personal_stats_points_used')=='1') $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'6241e58edfdsf735f3a2618fd7fff','KEY'=>do_lang_tempcode('COUNT_POINTS_USED'),'VALUE'=>integer_format(points_used($member_id)))));
		if (get_option('points_show_personal_stats_total_points')=='1') $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'3e6183abf9054574c0cd292d25a4fe5c','KEY'=>do_lang_tempcode('COUNT_POINTS_EVER'),'VALUE'=>integer_format(total_points($member_id)))));
		if (get_option('points_show_personal_stats_gift_points_left')=='1') $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'6241e5ssd45ddsdsdsa2618fd7fff','KEY'=>do_lang_tempcode('COUNT_GIFT_POINTS_LEFT'),'VALUE'=>integer_format(get_gift_points_to_give($member_id)))));
		if (get_option('points_show_personal_stats_gift_points_used')=='1') $details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'6241eddsd4sdddssdsa2618fd7fff','KEY'=>do_lang_tempcode('COUNT_GIFT_POINTS_USED'),'VALUE'=>integer_format(get_gift_points_used($member_id)))));
	}

	// Links to usergroups
	if (get_option('show_personal_usergroup')=='1')
	{
		$group_id=$GLOBALS['FORUM_DRIVER']->mrow_group($GLOBALS['FORUM_DRIVER']->get_member_row($member_id));
		$usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list();
		if (array_key_exists($group_id,$usergroups))
		{
			if (get_forum_type()=='ocf')
			{
				$group_url=build_url(array('page'=>'groups','type'=>'view','id'=>$group_id),get_module_zone('groups'));
				$hyperlink=hyperlink($group_url,$usergroups[$group_id],false,true);
				$details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE_COMPLEX',array('_GUID'=>'sas41eddsd4sdddssdsa2618fd7fff','KEY'=>do_lang_tempcode('GROUP'),'VALUE'=>$hyperlink)));
			} else
			{
				$details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'65180134fbc4cf7e227011463d466677','KEY'=>do_lang_tempcode('GROUP'),'VALUE'=>$usergroups[$group_id])));
			}
		}
	}

	// Last visit time
	if (get_option('show_personal_last_visit')=='1')
	{
		$row=$GLOBALS['FORUM_DRIVER']->get_member_row($member_id);
		$last_visit=$GLOBALS['FORUM_DRIVER']->mrow_lastvisit($row);
		$_last_visit=get_timezoned_date($last_visit,false);
		$details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'sas41eddsdsdsdsdsa2618fd7fff','KEY'=>do_lang_tempcode('LAST_HERE'),'RAW_KEY'=>strval($last_visit),'VALUE'=>$_last_visit)));
	}

	// Subscription expiry date
	if ((get_forum_type()=='ocf') && (addon_installed('ecommerce')))
	{
		$subscriptions=$GLOBALS['SITE_DB']->query_select('subscriptions',array('*'),array('s_member_id'=>$member_id,'s_state'=>'active','s_via'=>'manual'));
		if (count($subscriptions)>0)
		{
			require_code('ecommerce');
			foreach ($subscriptions as $sub)
			{
				$product_obj=find_product($sub['s_type_code']);
				$products=$product_obj->get_products(true);
				$product_name=$products[$sub['s_type_code']][4];
				$s_length=$products[$sub['s_type_code']][3]['length'];
				$s_length_units=$products[$sub['s_type_code']][3]['length_units']; // y-year, m-month, w-week, d-day
				$time_period_units=array('y'=>'year','m'=>'month','w'=>'week','d'=>'day');
				$expiry_time=strtotime('+'.strval($s_length).' '.$time_period_units[$s_length_units],$sub['s_time']);
				if ((($expiry_time-time())<(MANUAL_SUBSCRIPTION_EXPIRY_NOTICE*24*60*60)) && ($expiry_time>=time()))
				{
					$expiry_date=get_timezoned_date($expiry_time,false,false,false,true);
					require_lang('ecommerce');
					$details->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'65180134fbc4cf7e227011463d466677','KEY'=>do_lang_tempcode('SUBSCRIPTION_EXPIRY_MESSAGE',escape_html($product_name)),'VALUE'=>do_lang_tempcode('SUBSCRIPTION_EXPIRY_DATE',escape_html($expiry_date)))));
				}
			}
		}
	}

	// Subscription links
	if ((get_forum_type()=='ocf') && (addon_installed('ecommerce')) && (get_option('show_personal_sub_links')=='1') && (!has_zone_access($member_id,'adminzone')) && (has_actual_page_access($member_id,'purchase')))
	{
		require_lang('ecommerce');

		$usergroup_subs=$GLOBALS['FORUM_DB']->query_select('f_usergroup_subs',array('id','s_title','s_group_id','s_cost'),array('s_enabled'=>1));
		$in_one=false;
		$members_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups($member_id);
		foreach ($usergroup_subs as $i=>$sub)
		{
			$usergroup_subs[$i]['s_cost']=floatval($sub['s_cost']);
			if (in_array($sub['s_group_id'],$members_groups))
			{
				$in_one=true;
				break;
			}
		}
		if (!$in_one)
		{
			sort_maps_by($usergroup_subs,'s_cost');
			foreach ($usergroup_subs as $sub)
			{
				$url=build_url(array('page'=>'purchase','type'=>'message','product'=>'USERGROUP'.strval($sub['id'])),get_module_zone('purchase'));
				$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK',array('_GUID'=>'5c4a1f300b37722e587fe2f608f1ee3a','NAME'=>do_lang_tempcode('UPGRADE_TO',escape_html(get_translated_text($sub['s_title']))),'URL'=>$url)));
			}
		}
	}

	// Admin Zone link
	if ((get_option('show_personal_adminzone_link')=='1') && (has_zone_access($member_id,'adminzone')))
	{
		$url=build_url(array('page'=>''),'adminzone');
		$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK',array('_GUID'=>'ae243058f780f9528016f7854763a5fa','ACCESSKEY'=>'I','NAME'=>do_lang_tempcode('ADMIN_ZONE'),'URL'=>$url)));
	}

	// Conceded mode link
	if (($GLOBALS['SESSION_CONFIRMED_CACHE']==1) && (get_option('show_conceded_mode_link')=='1'))
	{
		$url=build_url(array('page'=>'login','type'=>'concede','redirect'=>(get_page_name()=='login')?NULL:SELF_REDIRECT),get_module_zone('login'));
		$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK_2',array('_GUID'=>'81fa81cfd3130e42996bf72b0e03d8aa','POST'=>true,'NAME'=>do_lang_tempcode('CONCEDED_MODE'),'DESCRIPTION'=>do_lang_tempcode('DESCRIPTION_CONCEDED_MODE'),'URL'=>$url)));
	}

	// Becomes-invisible link
	if (get_option('is_on_invisibility')=='1')
	{
		if ((array_key_exists(get_session_id(),$GLOBALS['SESSION_CACHE'])) && ($GLOBALS['SESSION_CACHE'][get_session_id()]['session_invisible']==0))
		{
			$visible=(array_key_exists(get_session_id(),$GLOBALS['SESSION_CACHE'])) && ($GLOBALS['SESSION_CACHE'][get_session_id()]['session_invisible']==0);
			$url=build_url(array('page'=>'login','type'=>'invisible','redirect'=>(get_page_name()=='login')?NULL:SELF_REDIRECT),get_module_zone('login'));
			$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK_2',array('_GUID'=>'2af618fe39444861c21cf0caec216227','NAME'=>do_lang_tempcode($visible?'INVISIBLE':'BE_VISIBLE'),'DESCRIPTION'=>'','URL'=>$url)));
		}
	}

	// Logout link
	$url=build_url(array('page'=>'login','type'=>'logout'),get_module_zone('login'));
	if (!is_httpauth_login())
		$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LOGOUT',array('_GUID'=>'d1caacba272a7ee3bf5b2a758e4e54ee','NAME'=>do_lang_tempcode('LOGOUT'),'URL'=>$url)));

	if (get_forum_type()=='ocf')
	{
		require_code('ocf_notifications');
		list(,$num_unread_pps)=generate_notifications($member_id);
	} else
	{
		$num_unread_pps=0;
	}

	return array($links,$details,$num_unread_pps);
}

/**
 * Use the url_title_cache table (a bit of a hack but saved changed the DB structure) to see if a check-op was performed has been performed within the last 30 days.
 *
 * @param  ID_TEXT		Special check code (often a URL but does not need to be).
 * @return boolean		Whether the check has happened recently.
*/
function handle_has_checked_recently($id_code)
{
	$last_check_test=$GLOBALS['SITE_DB']->query_select_value_if_there('url_title_cache','t_title',array('t_url'=>substr('!'.$id_code,0,255)));
	if ((is_null($last_check_test)) || (substr($last_check_test,0,1)!='!') || (intval(substr($last_check_test,1))+60*60*24*30<time())) // only re-checks every 30 days
	{
		// Show when it was last tested
		$GLOBALS['SITE_DB']->query_delete('url_title_cache',array('t_url'=>substr('!'.$id_code,0,255)),'',1); // To make sure it can insert below
		$GLOBALS['SITE_DB']->query_insert('url_title_cache',array('t_meta_title'=>'','t_keywords'=>'','t_description'=>'','t_image_url'=>'','t_title'=>'!'.strval(time()),'t_url'=>substr('!'.$id_code,0,255)),false,true); // To stop weird race-like conditions

		return false;
	}

	return true;
}

/**
 * Convert a string to an array, with utf-8 awareness where possible/required.
 *
 * @param  string			Input
 * @return array			Output
 */
function ocp_mb_str_split($str)
{
	$len=ocp_mb_strlen($str);
	$array=array();
	for ($i=0;$i<$len;$i++)
	{
		$array[]=ocp_mb_substr($str,$i,1);
	}
	return $array;
}

/**
 * Split a string into smaller chunks, with utf-8 awareness where possible/required. Can be used to split a string into smaller chunks which is useful for e.g. converting base64_encode output to match RFC 2045 semantics. It inserts end (defaults to "\r\n") every chunklen characters.
 *
 * @param  string		The input string.
 * @param  integer	The maximum chunking length.
 * @param  string		Split character.
 * @return string		The chunked version of the input string.
 */
function ocp_mb_chunk_split($str,$len=76,$glue="\r\n")
{
	if ($str=='') return '';
	$array=ocp_mb_str_split($str);
	$n=-1;
	$new='';
	foreach ($array as $char)
	{
		$n++;
		if ($n<$len)
		{
			$new.=$char;
		}
		elseif ($n==$len)
		{
			$new.=$glue.$char;
			$n=0;
		}
	}
	return $new;
}

/**
 * Log an action
 *
 * @param  ID_TEXT		The type of activity just carried out (a lang string)
 * @param  ?SHORT_TEXT	The most important parameter of the activity (e.g. id) (NULL: none)
 * @param  ?SHORT_TEXT	A secondary (perhaps, human readable) parameter of the activity (e.g. caption) (NULL: none)
 */
function _log_it($type,$a=NULL,$b=NULL)
{
	if (!function_exists('get_member')) return; // If this is during installation

	if ((get_option('site_closed')=='1') && (get_option('no_stats_when_closed')=='1')) return;

	// Run hooks, if any exist
	$hooks=find_all_hooks('systems','upon_action_logging');
	foreach (array_keys($hooks) as $hook)
	{
		require_code('hooks/systems/upon_action_logging/'.filter_naughty($hook));
		$ob=object_factory('upon_action_logging'.filter_naughty($hook),true);
		if (is_null($ob)) continue;
		$ob->run($type,$a,$b);
	}

	$ip=get_ip_address();
	$GLOBALS['SITE_DB']->query_insert('adminlogs',array('the_type'=>$type,'param_a'=>is_null($a)?'':substr($a,0,80),'param_b'=>is_null($b)?'':substr($b,0,80),'date_and_time'=>time(),'member_id'=>get_member(),'ip'=>$ip));

	static $logged=0;
	$logged++;

	if ($logged==1)
	{
		decache('side_tag_cloud');
		decache('main_staff_actions');
		decache('main_staff_checklist');
		decache('main_awards');
		decache('main_multi_content');
		decache('side_stored_menu'); // Due to the content counts in the CMS/Admin Zones
	}

	if ((get_page_name()!='admin_themewizard') && (get_page_name()!='admin_import'))
	{
		if ($logged<10) // Be extra sure it's not some kind of import, causing spam
		{
			require_all_lang();
			if (is_null($a)) $a=do_lang('NA');
			if (is_null($a)) $a=do_lang('NA');
			require_code('notifications');
			$subject=do_lang('ACTIONLOG_NOTIFICATION_MAIL_SUBJECT',get_site_name(),do_lang($type),array($a,$b));
			$mail=do_lang('ACTIONLOG_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape(do_lang($type)),array(is_null($a)?'':comcode_escape($a),is_null($b)?'':comcode_escape($b)));
			if (addon_installed('actionlog'))
				dispatch_notification('actionlog',$type,$subject,$mail);
		}
	}
}

/**
 * Generate a GUID.
 *
 * @return ID_TEXT		A GUID
 */
function generate_guid()
{
	// Calculate hash value
	$hash=md5(uniqid('',true));

	// Based on a comment in the PHP manual
	return sprintf('%08s-%04s-%04x-%04x-%12s',
		// 32 bits for "time_low"
		substr($hash, 0, 8),

		// 16 bits for "time_mid"
		substr($hash, 8, 4),

		// 16 bits for "time_hi_and_version",
		// four most significant bits holds version number 5
		(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,

		// 16 bits, 8 bits for "clk_seq_hi_res",
		// 8 bits for "clk_seq_low",
		// two most significant bits holds zero and one for variant DCE1.1
		(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

		// 48 bits for "node"
		substr($hash, 20, 12)
	);
}

