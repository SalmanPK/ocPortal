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
 * @package		ldap
 */

class Hook_config_default_ldap_is_windows
{

	/**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
	function get_details()
	{
		return array(
			'human_name'=>'LDAP_IS_WINDOWS',
			'the_type'=>'tick',
			'c_category'=>'USERS',
			'c_group'=>'LDAP',
			'explanation'=>'CONFIG_OPTION_ldap_is_windows',
			'shared_hosting_restricted'=>'1',
			'c_data'=>'',

			'addon'=>'ldap',
		);
	}

	/**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
	function get_default()
	{
		return (DIRECTORY_SEPARATOR=='/')?'0':'1';
	}

}


