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
 * @package		filedump
 */

class Hook_addon_registry_filedump
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
		return 'File/media library, for use in attachments or for general ad-hoc sharing.';
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
			'conflicts_with'=>array()
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
			'sources/hooks/systems/notifications/filedump.php',
			'sources/hooks/systems/config_default/filedump_show_stats_count_total_files.php',
			'sources/hooks/systems/config_default/filedump_show_stats_count_total_space.php',
			'sources/hooks/blocks/side_stats/stats_filedump.php',
			'sources/hooks/systems/addon_registry/filedump.php',
			'sources/hooks/systems/ajax_tree/choose_filedump_file.php',
			'sources/hooks/systems/do_next_menus/filedump.php',
			'sources/hooks/modules/admin_import_types/filedump.php',
			'FILE_DUMP_SCREEN.tpl',
			'uploads/filedump/index.html',
			'cms/pages/modules/filedump.php',
			'lang/EN/filedump.ini',
			'sources/hooks/systems/config_default/is_on_folder_create.php',
			'sources/hooks/modules/search/filedump.php',
			'sources/hooks/systems/rss/filedump.php',
			'sources/hooks/systems/occle_fs/home.php',
			'uploads/filedump/.htaccess',
			'themes/default/images/bigicons/filedump.png',
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
			'FILE_DUMP_SCREEN.tpl'=>'file_dump_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__file_dump_screen()
	{
		require_css('forms');

		return array(
			lorem_globalise(do_lorem_template('FILE_DUMP_SCREEN', array(
				'TITLE'=>lorem_title(),
				'FILES'=>placeholder_table(),
				'UPLOAD_FORM'=>placeholder_form(),
				'CREATE_FOLDER_FORM'=>placeholder_form(),
				'PLACE'=>placeholder_id()
			)), NULL, '', true)
		);
	}
}
