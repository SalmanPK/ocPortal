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
 * @package		quizzes
 */

require_code('content_fs');

class Hook_occle_fs_quizzes extends content_fs_base
{
	var $file_content_type='quiz';

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the content type
	 */
	function _enumerate_file_properties()
	{
		return array(
			'timeout'=>'?TIME',
			'start_text'=>'LONG_TRANS',
			'end_text'=>'LONG_TRANS',
			'end_text_fail'=>'LONG_TRANS',
			'notes'=>'LONG_TEXT',
			'percentage'=>'INTEGER',
			'open_time'=>'TIME',
			'close_time'=>'?TIME',
			'num_winners'=>'INTEGER',
			'redo_time'=>'?INTEGER',
			'type'=>'ID_TEXT',
			'validated'=>'BINARY',
			'text'=>'LONG_TRANS',
			'submitter'=>'member',
			'points_for_passing'=>'INTEGER',
			//'tied_newsletter'=>'?newsletter',
			'add_date'=>'?TIME',
			'meta_keywords'=>'LONG_TRANS',
			'meta_description'=>'LONG_TRANS',
		);
	}

	/**
	 * Standard modular add function for content hooks. Adds some content with the given label and properties.
	 *
	 * @param  SHORT_TEXT	Filename OR Content label
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return ~ID_TEXT		The content ID (false: error, could not create via these properties / here)
	 */
	function _file_add($filename,$path,$properties)
	{
		list($category_content_type,$category)=$this->_folder_convert_filename_to_id($path);
		list($properties,$label)=$this->_file_magic_filter($filename,$path,$properties);

		require_code('quiz2');

		$timeout=$this->_default_property_int($properties,'timeout');
		$start_text=$this->_default_property_str($properties,'start_text');
		$end_text=$this->_default_property_str($properties,'end_text');
		$end_text_fail=$this->_default_property_str($properties,'end_text_fail');
		$notes=$this->_default_property_str($properties,'notes');
		$percentage=$this->_default_property_int($properties,'percentage');
		$open_time=$this->_default_property_int_null($properties,'open_time');
		$close_time=$this->_default_property_int_null($properties,'close_time');
		$num_winners=$this->_default_property_int($properties,'num_winners');
		$redo_time=$this->_default_property_int($properties,'redo_time');
		$type=$this->_default_property_str($properties,'type');
		if ($type=='') $type='SURVEY';
		$validated=$this->_default_property_int_null($properties,'validated');
		if (is_null($validated)) $validated=1;
		$text=$this->_default_property_str($properties,'text');
		$submitter=$this->_default_property_int_null($properties,'submitter');
		$points_for_passing=$this->_default_property_int($properties,'points_for_passing');
		$tied_newsletter=NULL;//$this->_default_property_int_null($properties,'tied_newsletter');
		$add_time=$this->_default_property_int_null($properties,'add_date');
		$meta_keywords=$this->_default_property_str($properties,'meta_keywords');
		$meta_description=$this->_default_property_str($properties,'meta_description');
		$id=add_quiz($label,$timeout,$start_text,$end_text,$end_text_fail,$notes,$percentage,$open_time,$close_time,$num_winners,$redo_time,$type,$validated,$text,$submitter,$points_for_passing,$tied_newsletter,$add_time,$meta_keywords,$meta_description);
		return strval($id);
	}

	/**
	 * Standard modular delete function for content hooks. Deletes the content.
	 *
	 * @param  ID_TEXT	The filename
	 */
	function _file_delete($filename)
	{
		list($content_type,$content_id)=$this->_file_convert_filename_to_id($filename);

		require_code('quiz2');
		delete_quiz(intval($content_id));
	}
}
