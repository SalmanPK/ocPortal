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
 * @package		staff_messaging
 */

class Hook_config_default_messaging_forum_name
{

	/**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
	function get_details()
	{
		return array(
			'human_name'=>'_MESSAGING_FORUM_NAME',
			'the_type'=>'forum',
			'c_category'=>'ADMIN',
			'c_group'=>'CONTACT_US_MESSAGING',
			'explanation'=>'CONFIG_OPTION_messaging_forum_name',
			'shared_hosting_restricted'=>'0',
			'c_data'=>'',

			'addon'=>'staff_messaging',
		);
	}

	/**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
	function get_default()
	{
		return do_lang('MESSAGING_FORUM_NAME','','','',get_site_default_lang());
	}

}


