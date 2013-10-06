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
 * @package		page_management
 */

class Hook_addon_registry_page_management
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
		return 'Manage pages on the website.';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
			'tut_structure',
		);
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
			'previously_in_addon'=>array(
				'core_page_management'
			)
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
			'sources/hooks/systems/addon_registry/page_management.php',
			'VALIDATE_CHECK_SCREEN.tpl',
			'VALIDATE_CHECK_ERROR.tpl',
			'adminzone/pages/modules/admin_sitetree.php',
			'themes/default/images/bigicons/sitetree.png',
			'themes/default/images/pagepics/sitetreeeditor.png',
			'JAVASCRIPT_SITE_TREE_EDITOR.tpl',
			'SITE_TREE_EDITOR_SCREEN.tpl',
			'themes/default/images/pagepics/move.png',
			'themes/default/images/pagepics/deletepage.png',
			'themes/default/images/pagepics/addpagewizard.png',
			'themes/default/images/under_construction_animated.gif',
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
			'SITE_TREE_EDITOR_SCREEN.tpl'=>'administrative__site_tree_editor_screen',
			'VALIDATE_CHECK_SCREEN.tpl'=>'administrative__validate_check_screen',
			'VALIDATE_CHECK_ERROR.tpl'=>'administrative__validate_check_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__site_tree_editor_screen()
	{
		require_javascript('javascript_ajax');
		require_javascript('javascript_more');
		require_javascript('javascript_tree_list');
		require_javascript('javascript_dragdrop');
		require_javascript('javascript_site_tree_editor');
		require_lang('zones');
		return array(
			lorem_globalise(do_lorem_template('SITE_TREE_EDITOR_SCREEN', array(
				'TITLE'=>lorem_title()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__validate_check_screen()
	{
		require_lang('validation');
		$content=new ocp_tempcode();
		foreach (placeholder_array() as $val)
		{
			$content->attach(do_lorem_template('VALIDATE_CHECK_ERROR', array(
				'URL'=>placeholder_url(),
				'POINT'=>lorem_phrase()
			)));
		}
		return array(
			lorem_globalise(do_lorem_template('VALIDATE_CHECK_SCREEN', array(
				'TITLE'=>lorem_title(),
				'CONTENTS'=>$content
			)), NULL, '', true)
		);
	}
}
