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

/**
 * Put a member into a usergroup temporarily / extend such a temporary usergroup membership. Note that if people are subsequently removed from the usergroup they won't be put back in; this allows the admin to essentially cancel the subscription - however, if it is then extended, they do keep the time they had before too.
 *
 * @param  MEMBER		The member going in the usergroup.
 * @param  GROUP		The usergroup.
 * @param  integer	The number of minutes (may be negative to take time away).
 * @param  boolean	Whether to put the member into as a primary group if this is a new temporary membership (it is recommended to NOT use this, since we don't track the source group and hence on expiry the member is put back to the first default group - but also generally you probably don't want to box yourself in with moving people's primary group, it ties your future flexibility down a lot).
 */
function bump_member_group_timeout($member_id,$group_id,$num_minutes,$prefer_for_primary_group=false)
{
	// We don't want guests here!
	if (is_guest($member_id)) fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));

	require_code('ocf_groups_action');
	require_code('ocf_groups_action2');
	require_code('ocf_members');

	// Add to group if not already there
	$test=in_array($group_id,$GLOBALS['FORUM_DRIVER']->get_members_groups($member_id));
	if (!$test)
	{
		// Add them to the group
		if ((get_value('unofficial_ecommerce')=='1') && (get_forum_type()!='ocf'))
		{
			$GLOBALS['FORUM_DB']->add_member_to_group($member_id,$group_id);
		} else
		{
			if ($prefer_for_primary_group)
			{
				$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_update('f_members',array('m_primary_group'=>$group_id),array('id'=>$member_id),'',1);
				$GLOBALS['FORUM_DRIVER']->MEMBER_ROWS_CACHED=array();
			} else
			{
				ocf_add_member_to_group($member_id,$group_id);
			}
		}
	}

	// Extend or add, depending on whether they're in it yet
	$existing_timeout=$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_value_null_ok('f_group_member_timeouts','timeout',array('member_id'=>$member_id,'group_id'=>$group_id));
	if (is_null($existing_timeout))
	{
		// Add
		$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_insert('f_group_member_timeouts',array(
			'member_id'=>$member_id,
			'group_id'=>$group_id,
			'timeout'=>time()+60*$num_minutes,
		));
	} else
	{
		// Extend
		$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_update('f_group_member_timeouts',array(
			'timeout'=>$existing_timeout+60*$num_minutes,
		),array(
			'member_id'=>$member_id,
			'group_id'=>$group_id,
		),'',1);
	}

	global $USERS_GROUPS_CACHE,$GROUP_MEMBERS_CACHE;
	$USERS_GROUPS_CACHE=array();
	$GROUP_MEMBERS_CACHE=array();
}

/**
 * Handle auto-removal of timed-out members.
 */
function cleanup_member_timeouts()
{
	if (function_exists('set_time_limit')) @set_time_limit(0);

	require_code('ocf_groups_action');
	require_code('ocf_groups_action2');
	require_code('ocf_members');

	$db=(get_forum_type()=='ocf')?$GLOBALS['FORUM_DB']:$GLOBALS['SITE_DB'];
	$start=0;
	$time=time();
	do
	{
		$timeouts=$db->query('SELECT member_id,group_id FROM '.$db->get_table_prefix().'f_group_member_timeouts WHERE timeout<'.strval($time),100,$start);
		foreach ($timeouts as $timeout)
		{
			$member_id=$timeout['member_id'];
			$group_id=$timeout['group_id'];

			$test=in_array($group_id,$GLOBALS['FORUM_DRIVER']->get_members_groups($member_id));
			if ($test) // If they're still in it
			{
				if ((get_value('unofficial_ecommerce')=='1') && (get_forum_type()!='ocf'))
				{
					$GLOBALS['FORUM_DB']->remove_member_from_group($member_id,$group_id);
				} else
				{
					if ($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_primary_group')==$group_id)
					{
						$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_update('f_members',array('m_primary_group'=>get_first_default_group()),array('id'=>$member_id),'',1);
						$GLOBALS['FORUM_DRIVER']->MEMBER_ROWS_CACHED=array();
					}
					$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_delete('f_group_members',array('gm_group_id'=>$group_id,'gm_member_id'=>$member_id),'',1);
				}

				global $USERS_GROUPS_CACHE,$GROUP_MEMBERS_CACHE;
				$USERS_GROUPS_CACHE=array();
				$GROUP_MEMBERS_CACHE=array();
			}
		}
		$start+=100;
	}
	while (count($timeouts)==100);

	$timeouts=$db->query('DELETE FROM '.$db->get_table_prefix().'f_group_member_timeouts WHERE timeout<'.strval($time));
}
