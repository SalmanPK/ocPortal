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
 * @package		chat
 */

class Hook_occle_command_send_chatmessage
{
	/**
	 * Standard modular run function for OcCLE hooks.
	 *
	 * @param  array	The options with which the command was called
	 * @param  array	The parameters with which the command was called
	 * @param  object	A reference to the OcCLE filesystem object
	 * @return array	Array of stdcommand, stdhtml, stdout, and stderr responses
	 */
	function run($options,$parameters,&$occle_fs)
	{
		if ((array_key_exists('h',$options)) || (array_key_exists('help',$options))) return array('',do_command_help('send_chatmessage',array('h'),array(true,true)),'','');
		else
		{
			if (!array_key_exists(0,$parameters)) return array('','','',do_lang('MISSING_PARAM','1','send_chatmessage'));
			if (!array_key_exists(1,$parameters)) return array('','','',do_lang('MISSING_PARAM','2','send_chatmessage'));

			require_code('chat');

			if (is_numeric($parameters[0])) $chatroom=$parameters[0];
			elseif ($parameters[0]=='first-watched')
			{
				$_chatroom=get_value('occle_watched_chatroom');
				$chatroom=is_null($_chatroom)?$GLOBALS['SITE_DB']->query_select_value_if_there('chat_rooms','id',NULL,'ORDER BY id'):intval($_chatroom);
			}
			else $chatroom=get_chatroom_id($parameters[0]);

			if (is_null($chatroom)) return array('','','',do_lang('MISSING_RESOURCE'));

			chat_post_message($chatroom,$parameters[1],get_option('chat_default_post_font'),get_option('chat_default_post_colour'));

			return array('','',do_lang('SUCCESS'),'');
		}
	}

}

