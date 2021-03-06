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
 * @package		ocf_member_avatars
 */

class Hook_addon_registry_ocf_member_avatars
{

	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Member avatars.';
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(),
			'recommends'=>array(),
			'conflicts_with'=>array(),
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(

			'sources/hooks/systems/notifications/ocf_choose_avatar.php',
			'sources/hooks/systems/addon_registry/ocf_member_avatars.php',
			'OCF_EDIT_AVATAR_TAB.tpl',
			'uploads/ocf_avatars/index.html',
			'uploads/ocf_avatars/.htaccess',
			'sources/hooks/systems/profiles_tabs_edit/avatar.php',
			'themes/default/images/ocf_default_avatars/index.html',
		);
	}

	/**
	* Get mapping between template names and the method of this class that can render a preview of them
	*
	* @return array			The mapping
	*/
	function tpl_previews()
	{
		return array(
				'OCF_EDIT_AVATAR_TAB.tpl'=>'ocf_edit_avatar_tab',
				);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__ocf_edit_avatar_tab()
	{
		require_lang('ocf');
		require_css('ocf');
		$avatar	=	do_lorem_template('OCF_TOPIC_POST_AVATAR',array(
							'AVATAR'=>placeholder_image_url(),
							)
						);

		return array(
			lorem_globalise(
				do_lorem_template('OCF_EDIT_AVATAR_TAB',array(
					'USERNAME'=>lorem_word(),
					'AVATAR'=>$avatar,
					'WIDTH'=>placeholder_number(),
					'HEIGHT'=>placeholder_number(),
						)
			),NULL,'',true),
		);
	}
}
