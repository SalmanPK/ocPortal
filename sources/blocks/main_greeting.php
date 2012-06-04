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

class Block_main_greeting
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
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='is_guest()?NULL:array(get_member())';
		$info['ttl']=60*24*7;
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

		$forum=get_forum_type();

		$out=new ocp_tempcode();

		if ($forum!='none')
		{
			// Standard welcome back vs into greeting
			$member=get_member();
			if (is_guest($member))
			{
				$redirect=get_self_url(true,true);
				$login_url=build_url(array('page'=>'login','type'=>'misc','redirect'=>$redirect),get_module_zone('login'));
				$join_url=$GLOBALS['FORUM_DRIVER']->join_url();
				$join_bits=do_template('JOIN_OR_LOGIN',array('LOGIN_URL'=>$login_url,'JOIN_URL'=>$join_url));

				$p=do_lang_tempcode('WELCOME',$join_bits);
				$out->attach(paragraph($p,'hhrt4dsgdsgd'));
			} else
			{
				$out->attach(paragraph(do_lang_tempcode('WELCOME_BACK',escape_html($GLOBALS['FORUM_DRIVER']->get_username($member))),'gfgdf9gjd'));
			}
		}

		$message=get_option('welcome_message');
		if (has_actual_page_access(get_member(),'admin_config'))
		{
			if ($message!='') $message.=' [[page="_SEARCH:admin_config:category:SITE#group_GENERAL"]'.do_lang('EDIT').'[/page]]';
		}
		$out->attach(comcode_to_tempcode($message,NULL,true));

		return $out;
	}

}


