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
 * @package		wiki
 */

require_code('content_fs');

class Hook_occle_fs_wiki_page extends content_fs_base
{
	var $folder_content_type='wiki_page';
	var $file_content_type='wiki_post';

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the content type
	 */
	function _enumerate_folder_properties()
	{
		return array(
			'description'=>'LONG_TRANS',
			'notes'=>'LONG_TEXT',
			'hide_posts'=>'BINARY',
			'member'=>'member',
			'views'=>'INTEGER',
			'meta_keywords'=>'LONG_TRANS',
			'meta_description'=>'LONG_TRANS',
			'add_date'=>'TIME',
			'edit_date'=>'?TIME',
		);
	}

	/**
	 * Standard modular add function for content hooks. Adds some content with the given label and properties.
	 *
	 * @param  SHORT_TEXT	Filename OR Content label
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return ~ID_TEXT		The content ID (false: error)
	 */
	function _folder_add($filename,$path,$properties)
	{
		list($category_content_type,$category)=$this->_folder_convert_filename_to_id($path);

		list($properties,$label)=$this->_folder_magic_filter($filename,$path,$properties);

		require_code('wiki');

		$description=$this->_default_property_str($properties,'description');
		$notes=$this->_default_property_str($properties,'notes');
		$hide_posts=$this->_default_property_int($properties,'hide_posts');
		$member=$this->_default_property_int_null($properties,'member');
		$add_time=$this->_default_property_int_null($properties,'add_date');
		$edit_date=$this->_default_property_int_null($properties,'edit_date');
		$views=$this->_default_property_int($properties,'views');
		$meta_keywords=$this->_default_property_str($properties,'meta_keywords');
		$meta_description=$this->_default_property_str($properties,'meta_description');
		$id=wiki_add_page($label,$description,$notes,$hide_posts,$member,$add_time,$views,$meta_keywords,$meta_description,$edit_date);
		$the_order=$GLOBALS['SITE_DB']->query_value('wiki_children','MAX(the_order)',array('parent_id'=>$parent_id));

		if (is_null($the_order)) $the_order=-1;
		$the_order++;
		$GLOBALS['SITE_DB']->query_insert('wiki_children',array('parent_id'=>$parent_id,'child_id'=>$id,'the_order'=>$the_order,'label'=>$label));

		return strval($id);
	}

	/**
	 * Standard modular delete function for content hooks. Deletes the content.
	 *
	 * @param  ID_TEXT	The filename
	 */
	function _folder_delete($filename)
	{
		list($content_type,$content_id)=$this->_folder_convert_filename_to_id($filename);

		require_code('wiki');
		wiki_delete_page(intval($content_id));
	}

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the content type
	 */
	function _enumerate_file_properties()
	{
		return array(
			'validated'=>'BINARY',
			'send_notification'=>'BINARY',
			'views'=>'INTEGER',
			'poster'=>'member',
			'add_date'=>'TIME',
			'edit_date'=>'?TIME',
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

		if ($category=='') return false;

		require_code('wiki');

		$page_id=$this->_integer_category($category);
		$validated=$this->_default_property_int_null($properties,'validated');
		if (is_null($validated)) $validated=1;
		$member=$this->_default_property_int_null($properties,'poster');
		$send_notification=$this->_default_property_int($properties,'send_notification');
		$add_time=$this->_default_property_int_null($properties,'add_date');
		$edit_date=$this->_default_property_int_null($properties,'edit_date');
		$views=$this->_default_property_int($properties,'views');
		$id=wiki_add_post($page_id,$label,$validated,$member,$send_notification,$add_time,$views,$edit_date);
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

		require_code('wiki');
		wiki_delete_post(intval($content_id));
	}
}
