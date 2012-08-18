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
 * @package		core_ocf
 */

/**
 * List all the multi moderations that may be used in a certain forum.
 *
 * @param  AUTO_LINK		The forum we are listing for.
 * @return array 			List of multi moderations.
 */
function ocf_list_multi_moderations($forum_id)
{
	if (!addon_installed('ocf_multi_moderations')) return array();

	$rows=$GLOBALS['FORUM_DB']->query_select('f_multi_moderations m LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND m.mm_name=t.id',array('mm_forum_multi_code','m.id','m.mm_name','text_original AS _mm_name'),NULL,'ORDER BY text_original');
	$out=array();
	if (count($rows)==0) return $out;

	$lots_of_forums=$GLOBALS['FORUM_DB']->query_select_value('f_forums','COUNT(*)')>200;
	if (!$lots_of_forums)
	{
		$all_forums=collapse_2d_complexity('id','f_parent_forum',$GLOBALS['FORUM_DB']->query_select('f_forums',array('id','f_parent_forum')));
	}
	foreach ($rows as $row)
	{
		if ($GLOBALS['RECORD_LANG_STRINGS_CONTENT'] || is_null($row['_mm_name'])) $row['_mm_name']=get_translated_text($row['mm_name'],$GLOBALS['FORUM_DB']);

		require_code('ocfiltering');
		if ($lots_of_forums)
		{
			$sql=ocfilter_to_sqlfragment($row['mm_forum_multi_code'],'id','f_forums','f_parent_forum','f_parent_forum','id',true,true,$GLOBALS['FORUM_DB']);
			if (!is_null($GLOBALS['FORUM_DB']->query_value_if_there('SELECT id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums WHERE id='.strval($forum_id).' AND ('.$sql.')')))
				$out[$row['id']]=$row['_mm_name'];
		} else
		{
			$idlist=ocfilter_to_idlist_using_memory($row['mm_forum_multi_code'],$all_forums,'f_forums','f_parent_forum','f_parent_forum','id',true,true,$GLOBALS['FORUM_DB']);
			if (in_array($forum_id,$idlist))
				$out[$row['id']]=$row['_mm_name'];
		}
	}
	return $out;
}

/**
 * Whether a certain member may perform multi moderations in a certain forum.
 *
 * @param  AUTO_LINK The forum.
 * @param  ?MEMBER	The member (NULL: current member).
 * @return boolean	Answer.
 */
function ocf_may_perform_multi_moderation($forum_id,$member_id=NULL)
{
	if (is_null($member_id)) $member_id=get_member();

	if (!ocf_may_moderate_forum($forum_id,$member_id)) return false;

	return has_privilege($member_id,'run_multi_moderations');
}

/**
 * Whether a certain member may give formal warnings to other members.
 *
 * @param  ?MEMBER	The member (NULL: current member).
 * @return boolean	Answer.
 */
function ocf_may_warn_members($member_id=NULL)
{
	if (is_null($member_id)) $member_id=get_member();

	return has_privilege($member_id,'warn_members');
}

/**
 * Get all the warning rows for a certain member.
 *
 * @param  MEMBER	The member.
 * @return array	The warning rows.
 */
function ocf_get_warnings($member_id)
{
	if (!addon_installed('ocf_warnings')) return array();

	return $GLOBALS['FORUM_DB']->query_select('f_warnings',array('*'),array('w_member_id'=>$member_id,'w_is_warning'=>1));
}
