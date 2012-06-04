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
 * @package		core_ocf
 */

class Hook_email_exists
{

	/**
	 * Standard modular run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
	 *
	 * @return tempcode  The snippet
	 */
	function run()
	{
		$val=get_param('name');

		$test=$GLOBALS['FORUM_DB']->query_value_null_ok('f_members','m_username',array('m_email_address'=>$val));
		if (is_null($test)) return new ocp_tempcode();

		require_lang('ocf');

		return make_string_tempcode(strip_tags(str_replace(array('&lsquo;','&rsquo;','&ldquo;','&rdquo;'),array('"','"','"','"'),html_entity_decode(do_lang('EMAIL_ADDRESS_IN_USE',escape_html(get_site_name())),ENT_QUOTES))));
	}

}
