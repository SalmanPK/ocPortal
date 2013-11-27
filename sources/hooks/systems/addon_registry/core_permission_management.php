<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_permission_management
 */

class Hook_addon_registry_core_permission_management
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
		return 'Manage permissions.';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
			'tut_permissions',
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
			'conflicts_with'=>array()
		);
	}

	/**
	 * Explicitly say which icon should be used
	 *
	 * @return URLPATH		Icon
	 */
	function get_default_icon()
	{
		return 'themes/default/images/icons/48x48/menu/adminzone/security/permissions/privileges.png';
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes/default/images/icons/24x24/menu/adminzone/security/permissions/index.html',
			'themes/default/images/icons/48x48/menu/adminzone/security/permissions/index.html',
			'themes/default/images/icons/24x24/menu/adminzone/security/permissions/privileges.png',
			'themes/default/images/icons/48x48/menu/adminzone/security/permissions/privileges.png',
			'themes/default/images/icons/24x24/menu/adminzone/security/permissions/permission_tree_editor.png',
			'themes/default/images/icons/48x48/menu/adminzone/security/permissions/permission_tree_editor.png',
			'themes/default/css/permissions_editor.css',
			'themes/default/templates/PERMISSION_COLUMN_SIZER.tpl',
			'sources/hooks/systems/addon_registry/core_permission_management.php',
			'sources/hooks/systems/sitemap/privilege_category.php',
			'themes/default/templates/PERMISSION_KEYS_PERMISSIONS_SCREEN.tpl',
			'themes/default/templates/PERMISSION_KEYS_PERMISSION_ROW.tpl',
			'themes/default/templates/PERMISSION_SCREEN_PERMISSIONS_SCREEN.tpl',
			'themes/default/templates/PERMISSION_PRIVILEGES_SECTION.tpl',
			'themes/default/templates/PERMISSION_PRIVILEGES_SCREEN.tpl',
			'themes/default/templates/PERMISSION_CELL.tpl',
			'themes/default/templates/PERMISSION_HEADER_CELL.tpl',
			'themes/default/templates/PERMISSION_ROW.tpl',
			'themes/default/templates/JAVASCRIPT_PERMISSIONS.tpl',
			'themes/default/templates/PERMISSIONS_TREE_EDITOR_SCREEN.tpl',
			'themes/default/templates/PERMISSION_KEYS_MESSAGE_ROW.tpl',
			'adminzone/pages/modules/admin_permissions.php',
			'themes/default/images/permlevels/0.png',
			'themes/default/images/permlevels/1.png',
			'themes/default/images/permlevels/2.png',
			'themes/default/images/permlevels/3.png',
			'themes/default/images/permlevels/index.html',
			'themes/default/images/permlevels/inherit.png',
			'themes/default/images/pte_view_help.png',
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
			'PERMISSIONS_TREE_EDITOR_SCREEN.tpl'=>'administrative__permissions_tree_editor_screen',
			'PERMISSION_HEADER_CELL.tpl'=>'administrative__permission_keys_permissions_screen',
			'PERMISSION_CELL.tpl'=>'administrative__permission_keys_permissions_screen',
			'PERMISSION_KEYS_PERMISSION_ROW.tpl'=>'administrative__permission_keys_permissions_screen',
			'PERMISSION_KEYS_MESSAGE_ROW.tpl'=>'administrative__permission_keys_permissions_screen',
			'PERMISSION_KEYS_PERMISSIONS_SCREEN.tpl'=>'administrative__permission_keys_permissions_screen',
			'PERMISSION_COLUMN_SIZER.tpl'=>'administrative__permission_screen_permissions_screen',
			'PERMISSION_ROW.tpl'=>'administrative__permission_screen_permissions_screen',
			'PERMISSION_SCREEN_PERMISSIONS_SCREEN.tpl'=>'administrative__permission_screen_permissions_screen',
			'PERMISSION_PRIVILEGES_SECTION.tpl'=>'administrative__permission_s_permissions_screen',
			'PERMISSION_PRIVILEGES_SCREEN.tpl'=>'administrative__permission_s_permissions_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__permissions_tree_editor_screen()
	{
		require_css('sitemap_editor');

		require_javascript('javascript_ajax');
		require_javascript('javascript_tree_list');
		require_javascript('javascript_more');
		require_lang('permissions');

		require_css('forms');

		$groups=new ocp_tempcode();
		foreach (placeholder_array() as $id=>$group_name)
		{
			$groups->attach(form_input_list_entry("$id", false, $group_name));
		}

		return array(
			lorem_globalise(do_lorem_template('PERMISSIONS_TREE_EDITOR_SCREEN',array(
				'USERGROUPS'=>placeholder_array(),
				'TITLE'=>lorem_title(),
				'INITIAL_GROUP'=>lorem_phrase(),
				'COLOR'=>lorem_phrase(),
				'GROUPS'=>$groups,
				'EDITOR'=>lorem_phrase()
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
	function tpl_preview__administrative__permission_keys_permissions_screen()
	{
		require_lang('permissions');

		require_css('forms');

		$color='b7b7b7';
		$header_cells=new ocp_tempcode();
		foreach (placeholder_array() as $id=>$name)
		{
			$header_cells->attach(do_lorem_template('PERMISSION_HEADER_CELL',array(
				'COLOR'=>$color,
				'GROUP'=>$name
			)));
		}

		$header_cells->attach(do_lorem_template('PERMISSION_HEADER_CELL',array(
			'COLOR'=>$color,
			'GROUP'=>'+/-'
		)));

		$rows=new ocp_tempcode();
		$p_rows=placeholder_array();
		foreach ($p_rows as $id=>$page)
		{
			$cells=new ocp_tempcode();
			$code='';

			foreach (placeholder_array() as $gid=>$g_name)
			{
				$cells->attach(do_lorem_template('PERMISSION_CELL',array(
					'CHECKED'=>true,
					'HUMAN'=>lorem_phrase(),
					'NAME'=>'p_' . strval($id) . '__' . strval($gid)
				)));
			}

			$rows->attach(do_lorem_template('PERMISSION_KEYS_PERMISSION_ROW',array(
				'ALL_OFF'=>false,
				'KEY'=>lorem_word(),
				'UID'=>"$id",
				'CODE'=>'',
				'CELLS'=>$cells
			)));
		}

		// Match-key messages
		$m_rows=array();
		$m_rows[]=array(
			'id'=>'new_1',
			'k_message'=>'',
			'k_match_key'=>''
		);

		$rows2=new ocp_tempcode();
		foreach ($m_rows as $row)
		{
			$msg=lorem_phrase();
			$rows2->attach(do_lorem_template('PERMISSION_KEYS_MESSAGE_ROW',array(
				'KEY'=>lorem_word(),
				'MSG'=>$msg,
				'UID'=>$row['id']
			)));
		}

		return array(
			lorem_globalise(do_lorem_template('PERMISSION_KEYS_PERMISSIONS_SCREEN',array(
				'TITLE'=>lorem_title(),
				'URL'=>placeholder_url(),
				'HEADER_CELLS'=>$header_cells,
				'ROWS'=>$rows,
				'ROWS2'=>$rows2,
				'COLS'=>'',
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
	function tpl_preview__administrative__permission_screen_permissions_screen()
	{
		require_lang('permissions');

		require_css('forms');

		$header_cells=new ocp_tempcode();
		foreach (placeholder_array() as $id=>$name)
		{
			$header_cells->attach(do_lorem_template('PERMISSION_HEADER_CELL',array(
				'COLOR'=>'b7b7b7',
				'GROUP'=>$name
			)));
		}
		$header_cells->attach(do_lorem_template('PERMISSION_HEADER_CELL',array(
			'COLOR'=>'b7b7b7',
			'GROUP'=>''
		)));

		$cols=new ocp_tempcode();
		foreach (placeholder_array() as $id=>$g_name)
		{
			$cols->attach(do_lorem_template('PERMISSION_COLUMN_SIZER'));
		}
		$k=0;
		$rows=new ocp_tempcode();
		foreach (placeholder_array() as $zone)
		{
			foreach (placeholder_array() as $page)
			{
				$cells=new ocp_tempcode();
				$code='';

				$has=true;
				foreach (placeholder_array() as $id=>$g_name)
				{
					$cells->attach(do_lorem_template('PERMISSION_CELL',array(
						'CHECKED'=>true,
						'HUMAN'=>lorem_phrase(),
						'NAME'=>"id_" . strval($k)
					)));
					$k++;
				}

				$rows->attach(do_lorem_template('PERMISSION_ROW',array(
					'HAS'=>true,
					'ABBR'=>lorem_word(),
					'PERMISSION'=>lorem_word_2(),
					'CELLS'=>$cells,
					'CODE'=>''
				)));
			}
		}

		return array(
			lorem_globalise(do_lorem_template('PERMISSION_SCREEN_PERMISSIONS_SCREEN',array(
				'COLS'=>'',
				'ZONE'=>lorem_phrase(),
				'TITLE'=>lorem_title(),
				'URL'=>placeholder_url(),
				'HEADER_CELLS'=>$header_cells,
				'ROWS'=>$rows
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
	function tpl_preview__administrative__permission_s_permissions_screen()
	{
		require_lang('permissions');

		require_css('forms');

		$sections=new ocp_tempcode();
		$rows=new ocp_tempcode();
		$header_cells=new ocp_tempcode();
		$cols=new ocp_tempcode();
		//permission rows
		$k=0;
		foreach (placeholder_array() as $permission)
		{
			$cells=new ocp_tempcode();

			foreach (placeholder_array() as $id=>$group)
			{
				$k++;
				$cells->attach(do_lorem_template('PERMISSION_CELL',array(
					'CHECKED'=>true,
					'HUMAN'=>lorem_phrase(),
					'NAME'=>$group . strval($k)
				)));
			}

			$tpl_map=array(
				'HAS'=>lorem_word(),
				'ABBR'=>$permission,
				'PERMISSION'=>lorem_phrase(),
				'CELLS'=>$cells,
				'CODE'=>'',
				'DESCRIPTION'=>lorem_phrase()
			);

			$rows->attach(do_lorem_template('PERMISSION_ROW', $tpl_map));

			$cols->attach(do_lorem_template('PERMISSION_COLUMN_SIZER'));

			$header_cells->attach(do_lorem_template('PERMISSION_HEADER_CELL',array(
				'COLOR'=>'FF00FF',
				'GROUP'=>$permission
			)));
		}

		$header_cells->attach(do_lorem_template('PERMISSION_HEADER_CELL',array(
			'COLOR'=>'FF00FF',
			'GROUP'=>'+/-'
		)));

		$sections->attach(do_lorem_template('PERMISSION_PRIVILEGES_SECTION',array(
			'HEADER_CELLS'=>$header_cells,
			'SECTION'=>$rows,
			'COLS'=>'',
			'CURRENT_SECTION'=>lorem_word()
		)));

		$out=do_lorem_template('PERMISSION_PRIVILEGES_SCREEN',array(
			'TITLE'=>lorem_title(),
			'URL'=>placeholder_url(),
			'SECTIONS'=>$sections
		));

		return array(
			lorem_globalise($out, NULL, '', true)
		);
	}
}
