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

class Hook_Profiles_Tabs_Edit_delete
{

	/**
	 * Find whether this hook is active.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return boolean		Whether this hook is active
	 */
	function is_active($member_id_of,$member_id_viewing)
	{
		return has_specific_permission($member_id_viewing,'delete_account') && (($member_id_of==$member_id_viewing) || (has_specific_permission($member_id_viewing,'assume_any_member')));
	}

	/**
	 * Standard modular render function for profile tabs edit hooks.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return array			A tuple: The tab title, the tab body text (may be blank), the tab fields, extra Javascript (may be blank) the suggested tab order
	 */
	function render_tab($member_id_of,$member_id_viewing)
	{
		$title=do_lang_tempcode('DELETE_MEMBER');

		$order=200;

		// Actualiser
		$delete_account=post_param_integer('delete',0);
		if ($delete_account==1)
		{
			if (is_guest($member_id_of))
				warn_exit(do_lang_tempcode('INTERNAL_ERROR'));

			ocf_delete_member($member_id_of);

			inform_exit(do_lang_tempcode('SUCCESS'));
		}

		// UI fields
		$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id_of);

		$text=do_lang_tempcode('_DELETE_MEMBER'.(($member_id_of==get_member())?'_SUICIDAL':''),$username);

		$fields=new ocp_tempcode();
		require_code('form_templates');
		$fields->attach(form_input_tick(do_lang_tempcode('DELETE'),do_lang_tempcode('DESCRIPTION_DELETE'),'delete',false));

		$javascript='';

		return array($title,$fields,$text,$javascript,$order);
	}

}


