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
 * @package		points
 */

/**
 * Standard code module initialisation function.
 */
function init__points()
{
	global $TOTAL_POINTS_CACHE;
	$TOTAL_POINTS_CACHE=array();
	global $POINTS_USED_CACHE;
	$POINTS_USED_CACHE=array();
	global $POINT_INFO_CACHE;
	$POINT_INFO_CACHE=array();
}

/**
 * Get the price of the specified item for sale in the Point Store (only for tableless items).
 *
 * @param  ID_TEXT		The name of the item
 * @return integer		The price of the item
 */
function get_price($item)
{
	return $GLOBALS['SITE_DB']->query_select_value('prices','price',array('name'=>$item));
}

/**
 * Get the total points in the specified member's account; some of these will probably have been spent already
 *
 * @param  MEMBER			The member
 * @return integer		The number of points the member has
 */
function total_points($member)
{
	if (!has_privilege($member,'use_points')) return 0;

	global $TOTAL_POINTS_CACHE;
	if (array_key_exists($member,$TOTAL_POINTS_CACHE)) return $TOTAL_POINTS_CACHE[$member];

	$points_gained_posting=$GLOBALS['FORUM_DRIVER']->get_post_count($member);
	$_points_gained=point_info($member);
	$points_gained_given=array_key_exists('points_gained_given',$_points_gained)?$_points_gained['points_gained_given']:0;
	$points_gained_rating=array_key_exists('points_gained_rating',$_points_gained)?$_points_gained['points_gained_rating']:0;
	$points_gained_voting=array_key_exists('points_gained_voting',$_points_gained)?$_points_gained['points_gained_voting']:0;
	$points_gained_cedi=array_key_exists('points_gained_seedy',$_points_gained)?$_points_gained['points_gained_seedy']:0;
	$points_gained_chat=array_key_exists('points_gained_chat',$_points_gained)?$_points_gained['points_gained_chat']:0;
	$points_posting=intval(get_option('points_posting'));
	$points_rating=intval(get_option('points_rating'));
	$points_voting=intval(get_option('points_voting'));
	$points_joining=intval(get_option('points_joining'));
	$points_per_day=intval(get_option('points_per_day',true));
	$points_chat=intval(get_option('points_chat',true));
	$points_cedi=intval(get_option('points_cedi',true));
	$points_gained_auto=$points_per_day*intval(floor(floatval(time()-$GLOBALS['FORUM_DRIVER']->get_member_join_timestamp($member))/floatval(60*60*24)));
	$points=$points_joining+$points_gained_chat*$points_chat+$points_gained_cedi*$points_cedi+$points_gained_posting*$points_posting+$points_gained_given+$points_gained_rating*$points_rating+$points_gained_voting*$points_voting+$points_gained_auto;

	$TOTAL_POINTS_CACHE[$member]=$points;

	return $points;
}

/**
 * Get the total points the specified member has used (spent).
 *
 * @param  MEMBER			The member
 * @return integer		The number of points the member has spent
 */
function points_used($member)
{
	global $POINTS_USED_CACHE;
	if (array_key_exists($member,$POINTS_USED_CACHE)) return $POINTS_USED_CACHE[$member];

	$_points=point_info($member);
	$points=array_key_exists('points_used',$_points)?$_points['points_used']:0;
	$POINTS_USED_CACHE[$member]=$points;

	return $points;
}

/**
 * Get the total points the specified member has
 *
 * @param  MEMBER			The member
 * @return integer		The number of points the member has
 */
function available_points($member)
{
	if (!has_privilege($member,'use_points')) return 0;

	return total_points($member)-points_used($member);
}

/**
 * Get all sorts of information about a specified member's point account.
 *
 * @param  MEMBER			The member the point info is of
 * @return array			The map containing the members point info (fields as enumerated in description)
 */
function point_info($member)
{
	require_code('lang');
	require_lang('points');

	global $POINT_INFO_CACHE;
	if (array_key_exists($member,$POINT_INFO_CACHE)) return $POINT_INFO_CACHE[$member];

	$values=$GLOBALS['FORUM_DRIVER']->get_custom_fields($member);
	if (is_null($values))
	{
		$values=array();
	}

	$POINT_INFO_CACHE[$member]=array();
	foreach ($values as $key=>$val)
	{
		if (!is_object($val))
			$POINT_INFO_CACHE[$member][$key]=intval($val);
	}

	return $POINT_INFO_CACHE[$member];
}

/**
 * Get the number of gift points used by the given member.
 *
 * @param  MEMBER			The member we want it for
 * @return integer		The number of gift points used by the member
 */
function get_gift_points_used($member)
{
	return intval($GLOBALS['SITE_DB']->query_select_value_if_there('gifts','SUM(amount)',array('gift_from'=>$member)));

	// We won't do it this way, number may not be reliable enough
	//$_used=point_info($member);
	//$used=$_used['gift_points_used'];
	//return $used;
}

/**
 * Get the number of gifts points to give that the given member has.
 *
 * @param  MEMBER			The member we want it for
 * @return integer		The number of gifts points to give that the given member has
 */
function get_gift_points_to_give($member)
{
	$used=get_gift_points_used($member);
	if (get_forum_type()=='ocf')
	{
		require_lang('ocf');
		require_code('ocf_groups');

		$base=ocf_get_member_best_group_property($member,'gift_points_base');
		$per_day=ocf_get_member_best_group_property($member,'gift_points_per_day');
	} else
	{
		$base=25;
		$per_day=1;
	}
	$available=$base+$per_day*intval(floor((time()-$GLOBALS['FORUM_DRIVER']->get_member_join_timestamp($member))/(60*60*24)))-$used;

	return $available;
}


