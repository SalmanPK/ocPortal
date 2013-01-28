<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ad_success
 */

/*
Simple script to track advertising purchase successes.
Requires super_logging enabled.

Assumes 'from' GET parameter used to track what campaign hits came from.

May be very slow to run.
*/

$success=array();
$joining=array();
$failure=array();
$users_done=array();
$advertiser_sessions=$GLOBALS['SITE_DB']->query('SELECT member_id,get,ip,date_and_time FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'stats WHERE date_and_time>'.(string)(time()-60*60*24*get_param_integer('days',1)).' AND get LIKE \''.db_encode_like('%<param>from=%').'\'');
foreach ($advertiser_sessions as $session)
{
	if (array_key_exists($session['member_id'],$users_done)) continue;
	$users_done[$session['member_id']]=1;

	$matches=array();
	if (!preg_match('#<param>from=([\w\d]+)</param>#',$session['get'],$matches)) continue;
	$from=$matches[1];
	$user=$session['member_id'];

	if (!array_key_exists($from,$success))
	{
		$success[$from]=0;
		$failure[$from]=0;
		$joining[$from]=0;
	}

	if (get_param_integer('track',0)==1)
	{
		echo '<b>Tracking information for <u>'.$from.'</u> visitor</b> ('.$session['ip'].')....<br />';
		$places=$GLOBALS['SITE_DB']->query('SELECT the_page,date_and_time,referer FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'stats WHERE member_id='.(string)intval($user).' AND date_and_time>='.(string)intval($session['date_and_time']).' ORDER BY date_and_time');
		foreach ($places as $place)
		{
			echo '<p>'.escape_html($place['the_page']).' at '.date('Y-m-d H:i:s',$place['date_and_time']).' (from '.escape_html(substr($place['referer'],0,200)).')</p>';
		}
	}

	$ip=$GLOBALS['SITE_DB']->query_select_value_if_there('stats','ip',array('the_page'=>'site/pages/modules/join.php','member_id'=>$user),'',1);
	$user=is_null($ip)?NULL:$GLOBALS['SITE_DB']->query_value_if_there('SELECT member_id FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'stats WHERE '.db_string_equal_to('ip',$ip).' AND member_id>0');
	if (!is_null($user)) $joining[$from]++;
	$test=is_null($user)?NULL:$GLOBALS['SITE_DB']->query_select_value_if_there('stats','id',array('the_page'=>'site/pages/modules_custom/purchase.php','member_id'=>$user));
	if (!is_null($test)) $success[$from]++; else $failure[$from]++;
}

echo '<p><b>Summary</b>...</p>';
echo 'Successes...';
print_r($success);
echo '<br />';
echo 'Joinings...';
print_r($joining);
echo '<br />';
echo 'Failures...';
print_r($failure);
