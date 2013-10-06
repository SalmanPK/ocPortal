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
 * @package		core_themeing
 */

class Hook_addon_registry_core_themeing
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
		return 'Themeing the website, via CSS, HTML, and images.';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
			'tut_themes',
			'tut_releasing_themes',
			'tut_tempcode',
			'tut_fixed_width',
			'tut_design',
			'tut_designer_themes',
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
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes_editor.css',
			'sources/hooks/systems/snippets/exists_theme.php',
			'sources/hooks/systems/config/templates_number_revisions_show.php',
			'sources/hooks/systems/config/templates_store_revisions.php',
			'adminzone/load_template.php',
			'sources/hooks/systems/addon_registry/core_themeing.php',
			'JAVASCRIPT_THEME_COLOURS.tpl',
			'THEME_IMAGE_MANAGE_SCREEN.tpl',
			'THEME_IMAGE_PREVIEW.tpl',
			'THEME_MANAGE_SCREEN.tpl',
			'THEME_MANAGE.tpl',
			'THEME_COLOUR_CHOOSER.tpl',
			'THEME_EDIT_CSS_SCREEN.tpl',
			'adminzone/pages/modules/admin_themes.php',
			'TEMPLATE_EDIT_SCREEN.tpl',
			'TEMPLATE_EDIT_SCREEN_DROPDOWN.tpl',
			'TEMPLATE_LIST_ENTRY.tpl',
			'TEMPLATE_LIST_SCREEN.tpl',
			'TEMPLATE_MANAGE_SCREEN.tpl',
			'TEMPLATE_EDIT_LINK.tpl',
			'TEMPLATE_EDIT_SCREEN_EDITOR.tpl',
			'TEMPLATE_TREE.tpl',
			'TEMPLATE_TREE_ITEM.tpl',
			'TEMPLATE_TREE_ITEM_WRAP.tpl',
			'TEMPLATE_TREE_NODE.tpl',
			'TEMPLATE_LIST.tpl',
			'TEMPLATE_LIST_WRAP.tpl',
			'JAVASCRIPT_THEMEING.tpl',
			'sources/themes2.php',
			'sources/themes3.php',
			'themes/default/images/bigicons/edit_css.png',
			'themes/default/images/bigicons/edit_templates.png',
			'themes/default/images/bigicons/manage_images.png',
			'themes/default/images/bigicons/manage_themes.png',
			'themes/default/images/pagepics/themes.png',
			'lang/EN/themes.ini',
			'sources/lorem.php',
			'sources/hooks/systems/config/enable_theme_img_buttons.php',
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
			'THEME_MANAGE.tpl'=>'administrative__theme_manage_screen',
			'THEME_MANAGE_SCREEN.tpl'=>'administrative__theme_manage_screen',
			'THEME_COLOUR_CHOOSER.tpl'=>'administrative__theme_edit_css_screen',
			'THEME_EDIT_CSS_SCREEN.tpl'=>'administrative__theme_edit_css_screen',
			'TEMPLATE_MANAGE_SCREEN.tpl'=>'administrative__template_manage_screen',
			'TEMPLATE_EDIT_SCREEN_DROPDOWN.tpl'=>'administrative__template_edit_screen',
			'TEMPLATE_EDIT_SCREEN_EDITOR.tpl'=>'administrative__template_edit_screen',
			'TEMPLATE_EDIT_SCREEN.tpl'=>'administrative__template_edit_screen',
			'THEME_IMAGE_MANAGE_SCREEN.tpl'=>'administrative__theme_image_manage_screen',
			'THEME_IMAGE_PREVIEW.tpl'=>'administrative__theme_image_preview',
			'TEMPLATE_LIST_ENTRY.tpl'=>'administrative__template_list_screen',
			'TEMPLATE_LIST_SCREEN.tpl'=>'administrative__template_list_screen',
			'TEMPLATE_TREE.tpl'=>'administrative__template_tree_screen',
			'TEMPLATE_TREE_ITEM.tpl'=>'administrative__template_tree_screen',
			'TEMPLATE_TREE_ITEM_WRAP.tpl'=>'administrative__template_tree_screen',
			'TEMPLATE_TREE_NODE.tpl'=>'administrative__template_tree_screen',
			'TEMPLATE_EDIT_LINK.tpl'=>'administrative__template_edit_link_screen',
			'TEMPLATE_LIST.tpl'=>'administrative__template_preview_screen',
			'TEMPLATE_LIST_WRAP.tpl'=>'administrative__template_preview_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__theme_manage_screen()
	{
		require_lang('zones');
		$themes=new ocp_tempcode();
		foreach (placeholder_array() as $value)
		{
			$themes->attach(do_lorem_template('THEME_MANAGE', array(
				'THEME_USAGE'=>lorem_phrase(),
				'SEED'=>'123456',
				'DATE'=>placeholder_time(),
				'RAW_DATE'=>placeholder_date_raw(),
				'NAME'=>$value,
				'DESCRIPTION'=>lorem_paragraph_html(),
				'AUTHOR'=>lorem_phrase(),
				'TITLE'=>lorem_phrase(),
				'CSS_URL'=>placeholder_url(),
				'TEMPLATES_URL'=>placeholder_url(),
				'IMAGES_URL'=>placeholder_url(),
				'DELETABLE'=>placeholder_table(),
				'EDIT_URL'=>placeholder_url(),
				'DELETE_URL'=>placeholder_url(),
				'SCREEN_PREVIEW_URL'=>placeholder_url()
			)));
		}

		$zones=array();
		foreach (placeholder_array() as $v)
		{
			$zones[]=array(
				'0'=>lorem_word(),
				'1'=>lorem_word_2()
			);
		}

		return array(
			lorem_globalise(do_lorem_template('THEME_MANAGE_SCREEN', array(
				'TITLE'=>lorem_title(),
				'THEMES'=>$themes,
				'THEME_DEFAULT_REASON'=>lorem_phrase(),
				'ZONES'=>$zones,
				'HAS_FREE_CHOICES'=>true,
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
	function tpl_preview__administrative__theme_edit_css_screen()
	{
		require_javascript('javascript_theme_colours');
		require_javascript('javascript_ajax');
		require_css('forms');

		$colour_chooser=do_lorem_template('THEME_COLOUR_CHOOSER', array(
			'COLOR'=>'#ffffff',
			'NAME'=>lorem_word(),
			'CONTEXT'=>lorem_sentence()
		));

		return array(
			lorem_globalise(do_lorem_template('THEME_EDIT_CSS_SCREEN', array(
				'PING_URL'=>placeholder_url(),
				'WARNING_DETAILS'=>'',
				'REVISION_HISTORY'=>placeholder_table(),
				'SWITCH_ICON'=>'page/'.placeholder_img_code('page'),
				'SWITCH_STRING'=>lorem_phrase(),
				'SWITCH_URL'=>placeholder_url(),
				'TITLE'=>lorem_title(),
				'THEME'=>lorem_phrase(),
				'CSS'=>lorem_phrase(),
				'URL'=>placeholder_url(),
				'FILE'=>lorem_phrase(),
				'ENTRIES'=>$colour_chooser,
				'OLD_CONTENTS'=>lorem_word()
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
	function tpl_preview__administrative__template_manage_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('TEMPLATE_MANAGE_SCREEN', array(
				'THEME'=>lorem_phrase(),
				'PING_URL'=>placeholder_url(),
				'WARNING_DETAILS'=>'',
				'TITLE'=>lorem_title(),
				'EDIT_FORM'=>placeholder_form()
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
	function tpl_preview__administrative__template_edit_screen()
	{
		$guids=array();
		foreach (placeholder_array() as $_guid)
		{
			$guids[]=array(
				'FILENAME'=>$_guid,
				'LINE'=>placeholder_number(),
				'THIS_GUID'=>$_guid
			);
		}

		$_parameters=new ocp_tempcode();
		foreach (placeholder_array() as $value)
		{
			$_parameters->attach(form_input_list_entry($value, false, $value));
		}

		$parameters=array();
		for ($i=0;$i<8;$i++)
		{
			$parameters[$i]=do_lorem_template('TEMPLATE_EDIT_SCREEN_DROPDOWN', array(
				'ID'=>placeholder_id().strval($i),
				'PARAMETERS'=>$_parameters,
				'NAME'=>lorem_word().strval($i),
				'LANG'=>lorem_phrase()
			));
		}

		$template_editors=new ocp_tempcode();

		$template_editors->attach(do_lorem_template('TEMPLATE_EDIT_SCREEN_EDITOR', array(
			'CODENAME'=>lorem_word(),
			'I'=>lorem_word(),
			'NAME'=>lorem_word(),
			'DISPLAY'=>'block',
			'GUIDS'=>$guids,
			'GUID'=>placeholder_id(),
			'ARITHMETICAL_SYMBOLS'=>$parameters[0],
			'FORMATTING_SYMBOLS'=>$parameters[1],
			'LOGICAL_SYMBOLS'=>$parameters[2],
			'ABSTRACTION_SYMBOLS'=>$parameters[3],
			'PARAMETERS'=>$parameters[4],
			'DIRECTIVES'=>$parameters[5],
			'PROGRAMMATIC_SYMBOLS'=>$parameters[6],
			'SYMBOLS'=>$parameters[7],
			'FILE'=>lorem_phrase(),
			'FILE_SAVE_TARGET'=>lorem_phrase(),
			'OLD_CONTENTS'=>lorem_paragraph(),
			'CONTENTS'=>lorem_paragraph(),
			'REVISION_HISTORY'=>lorem_phrase(),
			'PREVIEW_URL'=>placeholder_url()
		)));

		return array(
			lorem_globalise(do_lorem_template('TEMPLATE_EDIT_SCREEN', array(
				'MULTIPLE'=>lorem_phrase(),
				'FIRST_ID'=>placeholder_id(),
				'THEME'=>lorem_phrase(),
				'TEMPLATES'=>lorem_phrase(),
				'TITLE'=>lorem_title(),
				'PREVIEW_URL'=>placeholder_url(),
				'URL'=>placeholder_url(),
				'TEMPLATE_EDITORS'=>$template_editors
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
	function tpl_preview__administrative__theme_image_manage_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('THEME_IMAGE_MANAGE_SCREEN', array(
				'ADD_URL'=>placeholder_url(),
				'TITLE'=>lorem_title(),
				'FORM'=>placeholder_form()
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
	function tpl_preview__administrative__theme_image_preview()
	{
		return array(
			lorem_globalise(do_lorem_template('THEME_IMAGE_PREVIEW', array(
				'WIDTH'=>placeholder_number(),
				'HEIGHT'=>placeholder_number(),
				'URL'=>placeholder_image_url(),
				'UNMODIFIED'=>lorem_phrase()
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
	function tpl_preview__administrative__template_preview_screen()
	{
		$templates=new ocp_tempcode();
		$lis=new ocp_tempcode();
		$ftemp=new ocp_tempcode();
		$list=array();
		foreach (placeholder_array() as $v)
		{
			$list[]=$v;
		}
		foreach (placeholder_array() as $v)
		{
			$lis->attach(do_lorem_template('TEMPLATE_LIST', array(
				'URL'=>placeholder_url(),
				'COLOR'=>'green',
				'TEMPLATE'=>lorem_word(),
				'LIST'=>""
			)));
		}

		$post=do_lorem_template('TEMPLATE_LIST_WRAP', array(
			'LI'=>$lis,
			'TITLE'=>lorem_phrase()
		));

		return array(
			lorem_globalise($post, NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__template_tree_screen()
	{
		$parameters=array(
			'FILE'=>lorem_phrase(),
			'EDIT_URL'=>placeholder_url(),
			'CODENAME'=>lorem_word(),
			'ID'=>placeholder_random_id()
		);

		$tree=do_lorem_template('TEMPLATE_TREE_ITEM', $parameters);

		$middle=new ocp_tempcode();
		foreach (placeholder_array() as $value)
		{
			$middle->attach(do_lorem_template('TEMPLATE_TREE_ITEM_WRAP', array(
				'CONTENT'=>lorem_phrase()
			)));
		}

		$tree->attach(do_lorem_template('TEMPLATE_TREE_NODE', array(
			'ITEMS'=>$middle
		)));

		return array(
			lorem_globalise(do_lorem_template('TEMPLATE_TREE', array(
				'HIDDEN'=>'',
				'EDIT_URL'=>placeholder_url(),
				'TREE'=>$tree
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
	function tpl_preview__administrative__template_list_screen()
	{
		$ftemp=new ocp_tempcode();
		foreach (placeholder_array() as $v)
		{
			$ftemp->attach(do_lorem_template('TEMPLATE_LIST_ENTRY', array(
				'COUNT'=>placeholder_number(),
				'NAME'=>lorem_word(),
				'EDIT_URL'=>placeholder_url()
			)));
		}

		return array(
			lorem_globalise(do_lorem_template('TEMPLATE_LIST_SCREEN', array(
				'TITLE'=>lorem_title(),
				'TEMPLATES'=>$ftemp
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
	function tpl_preview__administrative__template_edit_link_screen()
	{
		$parameters=array(
			'FILE'=>lorem_phrase(),
			'EDIT_URL'=>placeholder_url(),
			'CODENAME'=>lorem_word(),
			'GUID'=>placeholder_id(),
			'ID'=>placeholder_random_id()
		);

		$param_info=do_lorem_template('PARAM_INFO', array(
			'MAP'=>$parameters
		));

		return array(
			lorem_globalise(do_lorem_template('TEMPLATE_EDIT_LINK', array(
				'PARAM_INFO'=>$param_info,
				'CONTENTS'=>lorem_paragraph_html(),
				'CODENAME'=>lorem_word(),
				'EDIT_URL'=>placeholder_url()
			)), NULL, '', true)
		);

	}
}
