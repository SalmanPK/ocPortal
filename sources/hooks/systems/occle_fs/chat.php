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
 * @package		chat
 */

require_code('content_fs');

class Hook_occle_fs_chat extends content_fs_base
{
	var $file_content_type='chat';

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the content type
	 */
	function _enumerate_file_properties()
	{
		return array(
			'welcome_message'=>'LONG_TRANS',
			'room_owner'=>'member',
			'allow'=>'SHORT_TEXT',
			'allow_groups'=>'SHORT_TEXT',
			'disallow'=>'SHORT_TEXT',
			'disallow_groups'=>'SHORT_TEXT',
			'room_lang'=>'LANGUAGE_NAME',
			'is_im'=>'BINARY',
		);
	}

	/**
	 * Standard modular date fetch function for content hooks. Defined when getting an edit date is not easy.
	 *
	 * @param  array			Content row (not full, but does contain the ID)
	 * @return ?TIME			The edit date or add date, whichever is higher (NULL: could not find one)
	 */
	function _get_file_edit_date($row)
	{
		$query='SELECT MAX(date_and_time) FROM '.get_table_prefix().'adminlogs WHERE '.db_string_equal_to('param_a',strval($row['id'])).' AND  ('.db_string_equal_to('the_type','ADD_CHATROOM').' OR '.db_string_equal_to('the_type','EDIT_CHATROOM').')';
		return $GLOBALS['SITE_DB']->query_value_if_there($query);
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

		require_code('chat2');

		$welcome=$this->_default_property_str($properties,'welcome_message');
		$room_owner=$this->_default_property_int_null($properties,'room_owner');
		$allow2=$this->_default_property_str($properties,'allow');
		$allow2_groups=$this->_default_property_str($properties,'allow_groups');
		$disallow2=$this->_default_property_str($properties,'disallow');
		$disallow2_groups=$this->_default_property_str($properties,'disallow_groups');
		$roomlang=$this->_default_property_str($properties,'room_lang');
		if ($roomlang=='') $roomlang=get_site_default_lang();
		$is_im=$this->_default_property_int($properties,'is_im');

		$id=add_chatroom($welcome,$label,$room_owner,$allow2,$allow2_groups,$disallow2,$disallow2_groups,$roomlang,$is_im);
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

		require_code('chat2');
		delete_chatroom(intval($content_id));
	}
}
