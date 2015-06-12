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
 * @package		core_language_editing
 */

class Hook_addon_registry_core_language_editing
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
		return 'Translate the software, or just change what it says for stylistic reasons.';
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
			'translations_editor.css',
			'sources/hooks/systems/addon_registry/core_language_editing.php',
			'JAVASCRIPT_TRANSLATE.tpl',
			'TRANSLATE_ACTION.tpl',
			'TRANSLATE_LINE.tpl',
			'TRANSLATE_LINE_CONTENT.tpl',
			'TRANSLATE_SCREEN.tpl',
			'TRANSLATE_SCREEN_CONTENT_SCREEN.tpl',
			'TRANSLATE_LANGUAGE_CRITICISE_SCREEN.tpl',
			'TRANSLATE_LANGUAGE_CRITICISE_FILE.tpl',
			'TRANSLATE_LANGUAGE_CRITICISM.tpl',
			'adminzone/pages/modules/admin_lang.php',
			'themes/default/images/bigicons/criticise_language.png',
			'themes/default/images/bigicons/language.png',
			'themes/default/images/pagepics/criticise_language.png',
			'themes/default/images/pagepics/language.png',
			'sources/hooks/systems/do_next_menus/language.php',
			'themes/default/images/tableitem/translate.png'
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
			'TRANSLATE_LANGUAGE_CRITICISM.tpl'=>'administrative__translate_language_criticise_screen',
			'TRANSLATE_LANGUAGE_CRITICISE_FILE.tpl'=>'administrative__translate_language_criticise_screen',
			'TRANSLATE_LANGUAGE_CRITICISE_SCREEN.tpl'=>'administrative__translate_language_criticise_screen',
			'TRANSLATE_ACTION.tpl'=>'administrative__translate_screen_content_screen',
			'TRANSLATE_LINE_CONTENT.tpl'=>'administrative__translate_screen_content_screen',
			'TRANSLATE_SCREEN_CONTENT_SCREEN.tpl'=>'administrative__translate_screen_content_screen',
			'TRANSLATE_LINE.tpl'=>'administrative__translate_screen',
			'TRANSLATE_SCREEN.tpl'=>'administrative__translate_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__translate_language_criticise_screen()
	{
		$file=new ocp_tempcode();
		$files='';
		foreach (placeholder_array() as $value)
		{
			$crit=do_lorem_template('TRANSLATE_LANGUAGE_CRITICISM', array(
				'CRITICISM'=>lorem_sentence()
			));
			$file->attach($crit);
		}
		$file_result=do_lorem_template('TRANSLATE_LANGUAGE_CRITICISE_FILE', array(
			'COMPLAINTS'=>$file,
			'FILENAME'=>do_lang_tempcode('NA_EM')
		));

		$files.=$file_result->evaluate();

		return array(
			lorem_globalise(do_lorem_template('TRANSLATE_LANGUAGE_CRITICISE_SCREEN', array(
				'TITLE'=>lorem_title(),
				'FILES'=>$files
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
	function tpl_preview__administrative__translate_screen_content_screen()
	{
		require_lang('lang');
		$lines=new ocp_tempcode();
		foreach (placeholder_array() as $key=>$value)
		{
			$actions=do_lorem_template('TRANSLATE_ACTION', array(
				'LANG_FROM'=>fallback_lang(),
				'LANG_TO'=>fallback_lang(),
				'NAME'=>'trans_' . strval($key),
				'OLD'=>$value
			));
			$lines->attach(do_lorem_template('TRANSLATE_LINE_CONTENT', array(
				'ID'=>strval($key),
				'NAME'=>'trans_' . strval($key),
				'OLD'=>$value,
				'CURRENT'=>$value,
				'ACTIONS'=>$actions,
				'PRIORITY'=>lorem_word()
			)));
		}

		return array(
			lorem_globalise(do_lorem_template('TRANSLATE_SCREEN_CONTENT_SCREEN', array(
				'LANG_NICE_NAME'=>lorem_word(),
				'LANG_NICE_ORIGINAL_NAME'=>lorem_word(),
				'TOO_MANY'=>lorem_phrase(),
				'INTERTRANS'=>lorem_phrase(),
				'TOTAL'=>placeholder_number(),
				'LANG'=>fallback_lang(),
				'LANG_ORIGINAL_NAME'=>fallback_lang(),
				'LINES'=>$lines,
				'TITLE'=>lorem_title(),
				'URL'=>placeholder_url(),
				'MAX'=>placeholder_number(),
				'PAGINATION'=>placeholder_pagination()
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
	function tpl_preview__administrative__translate_screen()
	{
		require_lang('lang');
		$lines='';
		foreach (placeholder_array() as $i=>$value)
		{
			$temp=do_lorem_template('TRANSLATE_LINE', array(
				'TRANSLATE_AUTO'=>$value,
				'DESCRIPTION'=>lorem_sentence(),
				'NAME'=>lorem_word().strval($i),
				'OLD'=>str_replace('\n', chr(10), $value),
				'CURRENT'=>$value,
				'ACTIONS'=>lorem_phrase()
			));
			$lines.=$temp->evaluate();
		}

		return array(
			lorem_globalise(do_lorem_template('TRANSLATE_SCREEN', array(
				'PAGE'=>lorem_phrase(),
				'INTERTRANS'=>lorem_phrase(),
				'LANG'=>fallback_lang(),
				'LINES'=>$lines,
				'TITLE'=>lorem_title(),
				'URL'=>placeholder_url()
			)), NULL, '', true)
		);
	}
}
