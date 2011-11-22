<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_fields
 */

class Hook_fields_picture
{

	// ==============
	// Module: search
	// ==============

	/**
	 * Get special Tempcode for inputting this field.
	 *
	 * @param  array			The row for the field to input
	 * @return ?array			List of specially encoded input detail rows (NULL: nothing special)
	 */
	function get_search_inputter($row)
	{
		return NULL;
	}

	/**
	 * Get special SQL from POSTed parameters for this field.
	 *
	 * @param  array			The row for the field to input
	 * @param  integer		We're processing for the ith row
	 * @return ?array			Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (NULL: nothing special)
	 */
	function inputted_to_sql_for_search($row,$i)
	{
		return NULL;
	}

	// ===================
	// Backend: fields API
	// ===================

	/**
	 * Get some info bits relating to our field type, that helps us look it up / set defaults.
	 *
	 * @param  ?array			The field details (NULL: new field)
	 * @param  ?boolean		Whether a default value cannot be blank (NULL: don't "lock in" a new default value)
	 * @param  ?string		The given default value as a string (NULL: don't "lock in" a new default value)
	 * @return array			Tuple of details (row-type,default-value-to-use,db row-type)
	 */
	function get_field_value_row_bits($field,$required=NULL,$default=NULL)
	{
		unset($field);
		return array('short_unescaped',$default,'short');
	}

	/**
	 * Convert a field value to something renderable.
	 *
	 * @param  array			The field details
	 * @param  mixed			The raw value
	 * @param  integer		Position in fieldset
	 * @param  ?array			List of fields the output is being limited to (NULL: N/A)
	 * @return mixed			Rendered field (tempcode or string)
	 */
	function render_field_value($field,$ev,$i,$only_fields)
	{
		if (is_object($ev)) return $ev;

		if ($ev=='') return '';
		
		$img_url=$ev;
		if (url_is_local($img_url)) $img_url=get_custom_base_url().'/'.$img_url;
		if ((get_option('is_on_gd')=='0') || (!function_exists('imagetypes')))
		{
			$img_thumb_url=$img_url;
		} else
		{
			$new_name=url_to_filename($ev);
			require_code('images');
			if (!is_saveable_image($new_name)) $new_name.='.png';
			$file_thumb=get_custom_file_base().'/uploads/auto_thumbs/'.$new_name;
			if (!file_exists($file_thumb))
			{
				convert_image($img_url,$file_thumb,-1,-1,intval(get_option('thumb_width')),false);
			}
			$img_thumb_url=get_custom_base_url().'/uploads/auto_thumbs/'.rawurlencode($new_name);
		}
		if (!array_key_exists('c_name',$field)) $field['c_name']='other';
		$tpl_set=$field['c_name'];

		$GLOBALS['META_DATA']+=array(
			'image'=>$img_url,
		);

		return do_template('CATALOGUE_'.$tpl_set.'_ENTRY_FIELD_PICTURE',array('I'=>is_null($only_fields)?'-1':strval($i),'CATALOGUE'=>$field['c_name'],'URL'=>$img_url,'THUMB_URL'=>$img_thumb_url),NULL,false,'CATALOGUE_DEFAULT_ENTRY_FIELD_PICTURE');
	}

	// ======================
	// Frontend: fields input
	// ======================

	/**
	 * Get form inputter.
	 *
	 * @param  string			The field name
	 * @param  string			The field description
	 * @param  array			The field details
	 * @param  ?string		The actual current value of the field (NULL: none)
	 * @param  boolean		Whether this is for a new entry
	 * @return ?array			A pair: The Tempcode for the input field, Tempcode for hidden fields (NULL: skip the field - it's not input)
	 */
	function get_field_inputter($_cf_name,$_cf_description,$field,$actual_value,$new)
	{
		$say_required=($field['cf_required']==1) && (($actual_value=='') || (is_null($actual_value)));
		$ffield=form_input_upload($_cf_name,$_cf_description,'field_'.strval($field['id']),$say_required,($field['cf_required']==1)?NULL/*so unlink option not shown*/:$actual_value,NULL,true,str_replace(' ','',get_option('valid_images')));

		$hidden=new ocp_tempcode();
		handle_max_file_size($hidden,'image');
		
		return array($ffield,$hidden);
	}

	/**
	 * Find the posted value from the get_field_inputter field
	 *
	 * @param  boolean		Whether we were editing (because on edit, it could be a fractional edit)
	 * @param  array			The field details
	 * @param  string			Where the files will be uploaded to
	 * @param  ?string		Former value of field (NULL: none)
	 * @return string			The value
	 */
	function inputted_to_field_value($editing,$field,$upload_dir='uploads/catalogues',$old_value=NULL)
	{
		$id=$field['id'];
		$tmp_name='field_'.strval($id);
		if (!fractional_edit())
		{
			require_code('uploads');
			$temp=get_url('',$tmp_name,$upload_dir,0,OCP_UPLOAD_IMAGE);
			$value=$temp[0];
			if (($editing) && ($value=='') && (post_param_integer($tmp_name.'_unlink',0)!=1))
				return STRING_MAGIC_NULL;

			if ((!is_null($old_value)) && ($old_value!='') && (($value!='') || (post_param_integer('custom_'.strval($field['id']).'_value_unlink',0)==1)))
			{
				@unlink(get_custom_file_base().'/'.rawurldecode($old_value));
				sync_file(rawurldecode($old_value));
			}
		} else
		{
			$value=STRING_MAGIC_NULL;
		}

		return $value;
	}
	
	/**
	 * The field is being deleted, so delete any necessary data
	 *
	 * @param  mixed			Current field value
	 */
	function cleanup($value)
	{
		if ($value!='')
		{
			@unlink(get_custom_file_base().'/'.rawurldecode($value));
			sync_file(rawurldecode($value));
		}
	}

}


