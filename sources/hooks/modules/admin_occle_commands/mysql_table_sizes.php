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
 * @package		occle
 */

class Hook_mysql_table_sizes
{
	/**
	* Standard modular run function for OcCLE hooks.
	*
	* @param  array	The options with which the command was called
	* @param  array	The parameters with which the command was called
	* @param  object  A reference to the OcCLE filesystem object
	* @return array	Array of stdcommand, stdhtml, stdout, and stderr responses
	*/
	function run($options,$parameters,&$occle_fs)
	{
		if ((array_key_exists('h',$options)) || (array_key_exists('help',$options))) return array('',do_command_help('mysql_table_sizes',array('h'),array(true,true)),'','');
		else
		{
			$db=$GLOBALS['SITE_DB'];
			require_code('files');
			$sizes=list_to_map('Name',$db->query('SHOW TABLE STATUS WHERE Name LIKE \''.db_encode_like($db->get_table_prefix().'%').'\''));
			foreach ($sizes as $key=>$vals)
			{
				$sizes[$key]=$vals['Data_length']+$vals['Index_length']-$vals['Data_free'];
			}
			asort($sizes);
			$out='';
			$out.='<table class="results_table"><thead><tr><th>'.do_lang('NAME').'</th><th>'.do_lang('SIZE').'</th></tr></thead>';
			foreach ($sizes as $key=>$val)
			{
				$out.='<tr><td>'.escape_html(preg_replace('#^'.preg_quote(get_table_prefix(),'#').'#','',$key)).'</td><td>'.escape_html(clean_file_size($val)).'</td></tr>';
			}
			$out.='</table>';

			if (count($parameters)!=0)
			{
				foreach ($parameters as $p)
				{
					if (substr($p,0,strlen(get_table_prefix()))==get_table_prefix()) $p=substr($p,strlen(get_table_prefix()));
					$out.='<h2>'.escape_html($p).'</h2>';
					if (array_key_exists(get_table_prefix().$p,$sizes))
					{
						$num_rows=$db->query_select_value($p,'COUNT(*)');
						if ($num_rows>0)
						{
							$row=$db->query_select($p,array('*'),NULL,'',1,mt_rand(0,$num_rows-1));
							$out.='<table class="results_table">';
							$val=mixed();
							foreach ($row[0] as $key=>$val)
							{
								if (!is_string($val)) $val=strval($val);
								$out.='<tr><td>'.escape_html($key).'</td><td>'.escape_html($val).'</td></tr>';
							}
							$out.='</table>';
						} else
						{
							$out.='<p>'.do_lang('NONE').'</p>';
						}
					} else
					{
						$out.='<p>'.do_lang('UNKNOWN').'</p>';
					}
				}
			}

			return array('',$out,'','');
		}
	}

}

