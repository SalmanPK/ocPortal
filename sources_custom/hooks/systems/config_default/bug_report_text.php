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
 * @package		tester
 */

class Hook_config_default_bug_report_text
{

	/**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
	function get_details()
	{
		return array(
			'human_name'=>'BUG_REPORT_TEXT',
			'the_type'=>'text',
			'c_category'=>'FEATURE',
			'c_group'=>'TESTER',
			'explanation'=>'CONFIG_OPTION_bug_report_text',
			'shared_hosting_restricted'=>'0',
			'c_data'=>'',

			'addon'=>'tester',
		);
	}

	/**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
	function get_default()
	{
		return do_lang('DEFAULT_BUG_REPORT_TEMPLATE');
	}

}


