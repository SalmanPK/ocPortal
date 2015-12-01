<?php

/*
	Parameters:

	order=date|random|username
	group_id
	limit
*/

$order='m_join_time DESC';
if (isset($map['order']))
{
	if ($map['order']=='random')
	{
		$order='RAND()';
	}
	if ($map['order']=='username')
	{
		$order='m_username';
	}
}

$where='m_avatar_url<>\'\'';
if (isset($map['param']))
{
	if (is_numeric($map['param']))
	{
		$group_id=intval($map['param']);
	} else
	{
		$group_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_groups g JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON t.id=g.g_name','g.id',array('text_original'=>$map['param']));
		if (is_null($group_id))
		{
			$ret=paragraph(do_lang_tempcode('MISSING_RESOURCE'),'','nothing_here');
			$ret->evaluate_echo();
			return;
		}
	}
	$where.=' AND (m_primary_group='.strval($group_id).' OR EXISTS(SELECT gm_member_id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_group_members x WHERE x.gm_member_id=m.id AND gm_validated=1 AND gm_group_id='.strval($group_id).'))';
}

$limit=isset($map['limit'])?intval($map['limit']):200;

require_code('ocf_members2');

$hooks=NULL;
if (is_null($hooks)) $hooks=find_all_hooks('modules','topicview');
$hook_objects=NULL;
if (is_null($hook_objects))
{
	$hook_objects=array();
	foreach (array_keys($hooks) as $hook)
	{
		require_code('hooks/modules/topicview/'.filter_naughty_harsh($hook));
		$object=object_factory('Hook_'.filter_naughty_harsh($hook),true);
		if (is_null($object)) continue;
		$hook_objects[$hook]=$object;
	}
}

$query='SELECT m.* FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members m WHERE '.$where.' ORDER BY '.$order;
$rows=$GLOBALS['FORUM_DB']->query($query,$limit);
foreach ($rows as $row)
{
	$url=$GLOBALS['FORUM_DRIVER']->member_profile_url($row['id']);

	$avatar_url=$row['m_avatar_url'];
	if (url_is_local($avatar_url)) $avatar_url=get_custom_base_url().'/'.$avatar_url;

	$username=$GLOBALS['FORUM_DRIVER']->get_username($row['id']);

	$tooltip=static_evaluate_tempcode(render_member_box($row['id'],true,$hooks,$hook_objects,false));

	echo '
		<div>
			<a href="'.escape_html($url).'"><img src="'.escape_html($avatar_url).'" /></a><br />

			<a href="'.escape_html($url).'" onblur="this.onmouseout(event);" onfocus="this.onmouseover(event);" onmouseover="if (typeof window.activate_tooltip!=\'undefined\') activate_tooltip(this,event,\''.escape_html(str_replace(chr(10),'\n',addslashes($tooltip))).'\',\'auto\');">'.escape_html($username).'</a><br />
		</div>
	';
}
