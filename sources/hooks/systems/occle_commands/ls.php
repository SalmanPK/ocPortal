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
 * @package		occle
 */

class Hook_occle_command_ls
{
	/**
	 * Standard modular run function for OcCLE hooks.
	 *
	 * @param  array	The options with which the command was called
	 * @param  array	The parameters with which the command was called
	 * @param  object A reference to the OcCLE filesystem object
	 * @return array	Array of stdcommand, stdhtml, stdout, and stderr responses
	 */
	function run($options,$parameters,&$occle_fs)
	{
		if ((array_key_exists('h',$options)) || (array_key_exists('help',$options))) return array('',do_command_help('ls',array('h'),array(true)),'','');
		else
		{
			if (!array_key_exists(0,$parameters)) $parameters[0]=$occle_fs->print_working_directory(true);
			else $parameters[0]=$occle_fs->_pwd_to_array($parameters[0]);

			if (!$occle_fs->_is_dir($parameters[0])) return array('','','',do_lang('NOT_A_DIR','1'));

			$listing=$occle_fs->listing($parameters[0]);

			return array(
				'',
				do_template('OCCLE_LS',array(
					'_GUID'=>'705c3382e34e3d73479521bb8d05902f',
					'DIRECTORY'=>$occle_fs->_pwd_to_string($parameters[0]),
					'DIRECTORIES'=>$occle_fs->prepare_dir_contents_for_listing($listing[0]),
					'FILES'=>$occle_fs->prepare_dir_contents_for_listing($listing[1]),
				)),
				'',
				''
			);
		}
	}

}

