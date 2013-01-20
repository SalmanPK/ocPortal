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
 * Shuffle an array into an array of columns.
 *
 * @param  integer		The number of columns
 * @param  array			The source array
 * @return array			Array of columns
 */
function shuffle_for($by,$in)
{
	// Split evenly into $by equal piles (if won't equally divide, bias so the last one is the smallest via 'ceil')
	$out_piles=array();
	for ($i=0;$i<$by;$i++)
	{
		$out_piles[$i]=array();
	}
	$cnt=count($in);
	$split_point=intval(ceil(floatval($cnt)/floatval($by)));
	$next_split_point=$split_point;
	$for=0;
	for ($i=0;$i<$cnt;$i++)
	{
		if ($i>=$next_split_point)
		{
			$for++;
			$next_split_point+=intval(ceil(floatval($cnt-$i)/floatval($by-$for)));
		}

		$out_piles[$for][]=$in[$i];
	}

	// Take one from each pile in turn until none are left, putting into $out
	$out=array();
	while (true)
	{
		for ($j=0;$j<$by;$j++)
		{
			$next=array_shift($out_piles[$j]);
			if (!is_null($next)) $out[]=$next; else break 2;
		}
	}

	// $out now holds our result
	return $out;
}

/**
 * Check to see if an IP address is banned.
 *
 * @param  string			The IP address to check for banning (potentially encoded with *'s)
 * @return boolean		Whether the IP address is banned
 */
function ip_banned($ip) // This is the very first query called, so we will be a bit smarter, checking for errors
{
	if (!addon_installed('securitylogging')) return false;

	$ip4=(strpos($ip,'.')!==false);
	if ($ip4)
	{
		$ip_parts=explode('.',$ip);
	} else
	{
		$ip_parts=explode(':',$ip);
	}

	global $SITE_INFO;
	if (((isset($SITE_INFO['known_suexec'])) && ($SITE_INFO['known_suexec']=='1')) || (is_writable_wrap(get_file_base().'/.htaccess')))
	{
		$bans=array();
		$ban_count=preg_match_all('#\ndeny from (.*)#',file_get_contents(get_file_base().'/.htaccess'),$bans);
		$ip_bans=array();
		for ($i=0;$i<$ban_count;$i++)
		{
			$ip_bans[]=array('ip'=>$bans[1][$i]);
		}
	} else
	{
		$ip_bans=persistant_cache_get('IP_BANS');
		if (is_null($ip_bans))
		{
			$ip_bans=$GLOBALS['SITE_DB']->query('SELECT ip FROM '.get_table_prefix().'usersubmitban_ip',NULL,NULL,true);
			if (!is_null($ip_bans))
			{
				persistant_cache_set('IP_BANS',$ip_bans);
			}
		}
		if (is_null($ip_bans)) critical_error('DATABASE_FAIL');
	}
	$self_ip=NULL;
	foreach ($ip_bans as $ban)
	{
		if ((($ip4) && (compare_ip_address_ip4($ban['ip'],$ip_parts))) || ((!$ip4) && (compare_ip_address_ip6($ban['ip'],$ip_parts))))
		{
			if (is_null($self_ip))
			{
				$self_host=ocp_srv('HTTP_HOST');
				if (($self_host=='') || (preg_match('#^localhost[\.\:$]#',$self_host)!=0))
				{
					$self_ip='';
				} else
				{
					if (preg_match('#(\s|,|^)gethostbyname(\s|$|,)#i',@ini_get('disable_functions'))==0)
					{
						$self_ip=gethostbyname($self_host);
					} else $self_ip='';
					if ($self_ip=='') $self_ip=ocp_srv('SERVER_ADDR');
				}
			}

			if (($self_ip!='') && (!compare_ip_address($ban['ip'],$self_ip))) continue;
			if (compare_ip_address($ban['ip'],'127.0.0.1')) continue;
			if (compare_ip_address($ban['ip'],'fe00:0000:0000:0000:0000:0000:0000:0000')) continue;
			return true;
		}
	}
	return false;
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

	if ((get_option('site_closed')=='1') && (get_option('no_stats_when_closed',true)==='1')) return;

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
	$GLOBALS['SITE_DB']->query_insert('adminlogs',array('the_type'=>$type,'param_a'=>is_null($a)?'':substr($a,0,80),'param_b'=>is_null($b)?'':substr($b,0,80),'date_and_time'=>time(),'the_user'=>get_member(),'ip'=>$ip));

	decache('side_tag_cloud');
	decache('main_staff_actions');
	decache('main_staff_checklist');
	decache('main_awards');
	decache('main_multi_content');

	if ((get_page_name()!='admin_themewizard') && (get_page_name()!='admin_import'))
	{
		static $logged=0;
		$logged++;
		if ($logged<10) // Be extra sure it's not some kind of import, causing spam
		{
			if (is_null($a)) $a=do_lang('NA');
			if (is_null($a)) $a=do_lang('NA');
			require_code('notifications');
			$subject=do_lang('ACTIONLOG_NOTIFICATION_MAIL_SUBJECT',get_site_name(),do_lang($type),array($a,$b));
			$mail=do_lang('ACTIONLOG_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape(do_lang($type)),array(is_null($a)?'':comcode_escape($a),is_null($b)?'':comcode_escape($b)));
			dispatch_notification('actionlog',$type,$subject,$mail);
		}
	}
}
