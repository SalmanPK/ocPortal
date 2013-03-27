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
 * @package		ocf_cpfs
 */

require_code('content_fs');

class Hook_occle_fs_cpfs extends content_fs_base
{
	var $file_content_type='cpf';

	/**
	 * Whether the filesystem hook is active.
	 *
	 * @return boolean		Whether it is
	 */
	function _is_active()
	{
		return (get_forum_type()=='ocf');
	}

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the content type
	 */
	function _enumerate_file_properties()
	{
		$ret=array(
			'description'=>'SHORT_TRANS',
			'locked'=>'BINARY',
			'default'=>'LONG_TEXT',
			'public_view'=>'BINARY',
			'owner_view'=>'BINARY',
			'owner_set'=>'BINARY',
			'type'=>'ID_TEXT',
			'required'=>'BINARY',
			'show_in_posts'=>'BINARY',
			'show_in_post_previews'=>'BINARY',
			'order'=>'INTEGER',
			'only_group'=>'LONG_TEXT',
			'show_on_join_form'=>'BINARY',
		);
		require_code('encryption');
		if (is_encryption_enabled())
		{
			$ret['encryption']='BINARY';
		}
		return $ret;
	}

	/**
	 * Standard modular date fetch function for content hooks. Defined when getting an edit date is not easy.
	 *
	 * @param  array			Content row (not full, but does contain the ID)
	 * @return ?TIME			The edit date or add date, whichever is higher (NULL: could not find one)
	 */
	function _get_file_edit_date($row)
	{
		$query='SELECT MAX(date_and_time) FROM '.get_table_prefix().'adminlogs WHERE '.db_string_equal_to('param_a',strval($row['id'])).' AND  ('.db_string_equal_to('the_type','ADD_CUSTOM_PROFILE_FIELD').' OR '.db_string_equal_to('the_type','EDIT_CUSTOM_PROFILE_FIELD').')';
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

		require_code('ocf_members_action');

		$description=$this->_default_property_str($properties,'description');
		$locked=$this->_default_property_int($properties,'locked');
		$default=$this->_default_property_int($properties,'default');
		$public_view=$this->_default_property_int($properties,'public_view');
		$owner_view=$this->_default_property_int($properties,'owner_view');
		$owner_set=$this->_default_property_int($properties,'owner_set');
		require_code('encryption');
		if (is_encryption_enabled())
			$encrypted=$this->_default_property_int($properties,'encrypted');
		else
			$encrypted=0;
		$type=$this->_default_property_str($properties,'type');
		$required=$this->_default_property_int($properties,'required');
		$show_in_posts=$this->_default_property_int($properties,'show_in_posts');
		$show_in_post_previews=$this->_default_property_int($properties,'show_in_post_previews');
		$order=$this->_default_property_int($properties,'order');
		$only_group=$this->_default_property_str($properties,'only_group');
		$show_on_join_form=$this->_default_property_int($properties,'show_on_join_form');

		$id=ocf_make_custom_field($label,$locked,$description,$default,$public_view,$owner_view,$owner_set,$encrypted,$type,$required,$show_in_posts,$show_in_post_previews,$order,$only_group,false,$show_on_join_form);
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

		require_code('ocf_members_action2');
		ocf_delete_custom_field(intval($content_id));
	}
}
