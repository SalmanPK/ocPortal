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
 * @package		core
 */

class Block_side_personal_stats
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
		$info['parameters']=array();
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		unset($map);

		require_css('side_blocks');

		$member=get_member();

		$forum=get_forum_type();

		$content=new ocp_tempcode();
		$links=new ocp_tempcode();
		if (!is_guest())
		{
			// Admins can jump user
			$has_su=(get_option('ocp_show_su')=='1') && (has_specific_permission(get_member(),'assume_any_member'));

			$staff_actions=new ocp_tempcode();

			$username=$GLOBALS['FORUM_DRIVER']->get_username(get_member());

			if ($forum!='none')
			{
				if ((!has_no_forum()) && (get_option('forum_show_personal_stats_posts')=='1'))
				{
					// Post count
					$content->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'371dfee46e8c40b1b109e0350055f8cc','KEY'=>do_lang_tempcode('COUNT_POSTSCOUNT'),'VALUE'=>integer_format($GLOBALS['FORUM_DRIVER']->get_post_count($member)))));
				}
				if ((!has_no_forum()) && (get_option('forum_show_personal_stats_topics')=='1'))
				{
					// Topic count
					$content->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('KEY'=>do_lang_tempcode('COUNT_TOPICSCOUNT'),'VALUE'=>integer_format($GLOBALS['FORUM_DRIVER']->get_topic_count($member)))));
				}

				// Member profile view link
				if (get_option('ocf_show_profile_link')=='1')
				{
					$url=$GLOBALS['FORUM_DRIVER']->member_profile_url($member,true,true);
					$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK',array('_GUID'=>'2c8648c953c802a9de41c3adeef0e97f','NAME'=>do_lang_tempcode('MY_PROFILE'),'URL'=>$url,'REL'=>'me')));
				}
			}

			// Point count and point profile link
			if (addon_installed('points'))
			{
				require_lang('points');
				require_code('points');
				if (get_option('points_show_personal_stats_points_left')=='1') $content->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'6241e58e30457576735f3a2618fd7fff','KEY'=>do_lang_tempcode('COUNT_POINTS_LEFT'),'VALUE'=>integer_format(available_points($member)))));
				if (get_option('points_show_personal_stats_points_used')=='1') $content->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'6241e58edfdsf735f3a2618fd7fff','KEY'=>do_lang_tempcode('COUNT_POINTS_USED'),'VALUE'=>integer_format(points_used($member)))));
				if (get_option('points_show_personal_stats_total_points')=='1') $content->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'3e6183abf9054574c0cd292d25a4fe5c','KEY'=>do_lang_tempcode('COUNT_POINTS_EVER'),'VALUE'=>integer_format(total_points($member)))));
				if (get_option('points_show_personal_stats_gift_points_left')=='1') $content->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'6241e5ssd45ddsdsdsa2618fd7fff','KEY'=>do_lang_tempcode('COUNT_GIFT_POINTS_LEFT'),'VALUE'=>integer_format(get_gift_points_to_give($member)))));
				if (get_option('points_show_personal_stats_gift_points_used')=='1') $content->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'6241eddsd4sdddssdsa2618fd7fff','KEY'=>do_lang_tempcode('COUNT_GIFT_POINTS_USED'),'VALUE'=>integer_format(get_gift_points_used($member)))));
			}

			if (get_option('ocp_show_personal_usergroup')=='1')
			{
				$group_id=$GLOBALS['FORUM_DRIVER']->pname_group($GLOBALS['FORUM_DRIVER']->pget_row($username));
				$usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list();
				if (array_key_exists($group_id,$usergroups))
				{
					if (get_forum_type()=='ocf')
					{
						$group_url=build_url(array('page'=>'groups','type'=>'view','id'=>$group_id),get_module_zone('groups'));
						$hyperlink=hyperlink($group_url,$usergroups[$group_id],false,true);
						$content->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE_COMPLEX',array('_GUID'=>'sas41eddsd4sdddssdsa2618fd7fff','KEY'=>do_lang_tempcode('GROUP'),'VALUE'=>$hyperlink)));
					} else
					{
						$content->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'65180134fbc4cf7e227011463d466677','KEY'=>do_lang_tempcode('GROUP'),'VALUE'=>$usergroups[$group_id])));
					}
				}
			}
			if (get_option('ocp_show_personal_last_visit')=='1')
			{
				$row=$GLOBALS['FORUM_DRIVER']->pget_row($username);
				if (get_forum_type()=='ocf')
				{
					$last_visit=intval(ocp_admirecookie('last_visit',strval($GLOBALS['FORUM_DRIVER']->pnamelast_visit($row))));
				} else
				{
					$last_visit=$GLOBALS['FORUM_DRIVER']->pnamelast_visit($row);
				}
				$_last_visit=get_timezoned_date($last_visit,false);
				$content->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINE',array('_GUID'=>'sas41eddsdsdsdsdsa2618fd7fff','KEY'=>do_lang_tempcode('LAST_HERE'),'RAW_KEY'=>strval($last_visit),'VALUE'=>$_last_visit)));
			}

			$avatar_url='';
			if (!has_no_forum())
			{
				if (get_option('ocp_show_avatar')==='1')
				{
					$avatar_url=$GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member);
				}
			}

			// Subscription links
			if ((get_forum_type()=='ocf') && (addon_installed('ecommerce')) && (get_option('ocp_show_personal_sub_links')=='1') && (!has_zone_access(get_member(),'adminzone')) && (has_actual_page_access(get_member(),'purchase')))
			{
				$usergroup_subs=$GLOBALS['FORUM_DB']->query_select('f_usergroup_subs',array('id','s_title','s_group_id','s_cost'),array('s_enabled'=>1));
				$in_one=false;
				$members_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups($member);
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
					global $M_SORT_KEY;
					$M_SORT_KEY='s_cost';
					usort($usergroup_subs,'multi_sort');
					foreach ($usergroup_subs as $sub)
					{
						$url=build_url(array('page'=>'purchase','type'=>'message','product'=>'USERGROUP'.strval($sub['id'])),get_module_zone('purchase'));
						$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK',array('NAME'=>do_lang_tempcode('UPGRADE_TO',escape_html(get_translated_text($sub['s_title']))),'URL'=>$url)));
					}
				}
			}

			// Admin Zone link
			if ((get_option('ocp_show_personal_adminzone_link')=='1') && (has_zone_access(get_member(),'adminzone')))
			{
				$url=build_url(array('page'=>''),'adminzone');
				$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK',array('_GUID'=>'ae243058f780f9528016f7854763a5fa','ACCESSKEY'=>'I','NAME'=>do_lang_tempcode('ADMIN_ZONE'),'URL'=>$url)));
			}

			// Conceded mode link
			if (($GLOBALS['SESSION_CONFIRMED']==1) && (get_option('ocp_show_conceded_mode_link')=='1'))
			{
				$url=build_url(array('page'=>'login','type'=>'concede','redirect'=>(get_page_name()=='login')?NULL:SELF_REDIRECT),get_module_zone('login'));
				$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK_2',array('_GUID'=>'81fa81cfd3130e42996bf72b0e03d8aa','POST'=>true,'NAME'=>do_lang_tempcode('CONCEDED_MODE'),'DESCRIPTION'=>do_lang_tempcode('DESCRIPTION_CONCEDED_MODE'),'URL'=>$url)));
			}

			// Becomes-invisible link
			if (get_option('is_on_invisibility')=='1')
			{
				$visible=(array_key_exists(get_session_id(),$GLOBALS['SESSION_CACHE'])) && ($GLOBALS['SESSION_CACHE'][get_session_id()]['session_invisible']==0);
				$url=build_url(array('page'=>'login','type'=>'invisible','redirect'=>(get_page_name()=='login')?NULL:SELF_REDIRECT),get_module_zone('login'));
				$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LINK_2',array('NAME'=>do_lang_tempcode($visible?'INVISIBLE':'BE_VISIBLE'),'DESCRIPTION'=>'','URL'=>$url)));
			}

			// Logout link
			$url=build_url(array('page'=>'login','type'=>'logout'),get_module_zone('login'));
			if (!is_httpauth_login())
				$links->attach(do_template('BLOCK_SIDE_PERSONAL_STATS_LOGOUT',array('_GUID'=>'d1caacba272a7ee3bf5b2a758e4e54ee','NAME'=>do_lang_tempcode('LOGOUT'),'URL'=>$url)));

			return do_template('BLOCK_SIDE_PERSONAL_STATS',array('_GUID'=>'99f9bc3387102daaeeedf99843b0502e','AVATAR_URL'=>$avatar_url,'LINKS'=>$links,'HAS_SU'=>$has_su,'CONTENT'=>$content,'USERNAME'=>$username,'STAFF_ACTIONS'=>$staff_actions));
		} else
		{
			$title=do_lang_tempcode('NOT_LOGGED_IN');

			if ((get_page_name()!='join') && (get_page_name()!='login'))
			{
				if (count($_POST)>0)
				{
					$_this_url=build_url(array('page'=>''),'',array('keep_session'=>1,'redirect'=>1));
				} else
				{
					$_this_url=build_url(array('page'=>'_SELF'),'_SELF',array('keep_session'=>1,'redirect'=>1),true);
				}
			} else
			{
				$_this_url=build_url(array('page'=>''),'',array('keep_session'=>1,'redirect'=>1));
			}
			$this_url=$_this_url->evaluate();
			$login_url=build_url(array('page'=>'login','type'=>'login','redirect'=>$this_url),get_module_zone('login'));
			$full_link=build_url(array('page'=>'login','type'=>'misc','redirect'=>$this_url),get_module_zone('login'));
			$join_url=(get_forum_type()!='none')?$GLOBALS['FORUM_DRIVER']->join_url():'';
			return do_template('BLOCK_SIDE_PERSONAL_STATS_NO',array('_GUID'=>'32aade68b98dfd191f0f84c6648f7dde','TITLE'=>$title,'FULL_LINK'=>$full_link,'JOIN_LINK'=>$join_url,'LOGIN_URL'=>$login_url));
		}
	}

}


