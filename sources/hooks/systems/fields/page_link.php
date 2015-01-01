<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_fields
 */

/**
 * Hook class.
 */
class Hook_fields_page_link
{
    // ==============
    // Module: search
    // ==============

    /**
     * Get special Tempcode for inputting this field.
     *
     * @param  array                    $row The row for the field to input
     * @return ?array                   List of specially encoded input detail rows (null: nothing special)
     */
    public function get_search_inputter($row)
    {
        return null;
    }

    /**
     * Get special SQL from POSTed parameters for this field.
     *
     * @param  array                    $row The row for the field to input
     * @param  integer                  $i We're processing for the ith row
     * @return ?array                   Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (null: nothing special)
     */
    public function inputted_to_sql_for_search($row, $i)
    {
        return null;
    }

    // ===================
    // Backend: fields API
    // ===================

    /**
     * Get some info bits relating to our field type, that helps us look it up / set defaults.
     *
     * @param  ?array                   $field The field details (null: new field)
     * @param  ?boolean                 $required Whether a default value cannot be blank (null: don't "lock in" a new default value)
     * @param  ?string                  $default The given default value as a string (null: don't "lock in" a new default value)
     * @return array                    Tuple of details (row-type,default-value-to-use,db row-type)
     */
    public function get_field_value_row_bits($field, $required = null, $default = null)
    {
        return array('short_unescaped', $default, 'short');
    }

    /**
     * Convert a field value to something renderable.
     *
     * @param  array                    $field The field details
     * @param  mixed                    $ev The raw value
     * @return mixed                    Rendered field (tempcode or string)
     */
    public function render_field_value($field, $ev)
    {
        if (is_object($ev)) {
            return $ev;
        }

        if ($ev == '') {
            return '';
        }

        $_ev = explode(' ', $ev, 2);
        if (!array_key_exists(1, $_ev)) {
            $_ev[1] = $_ev[0];
        }

        list($zone, $attributes,) = page_link_decode($_ev[0]);
        $url = build_url($attributes, $zone);

        return hyperlink($url, escape_html($_ev[1]));
    }

    // ======================
    // Frontend: fields input
    // ======================

    /**
     * Get form inputter.
     *
     * @param  string                   $_cf_name The field name
     * @param  string                   $_cf_description The field description
     * @param  array                    $field The field details
     * @param  ?string                  $actual_value The actual current value of the field (null: none)
     * @param  boolean                  $new Whether this is for a new entry
     * @return ?tempcode                The Tempcode for the input field (null: skip the field - it's not input)
     */
    public function get_field_inputter($_cf_name, $_cf_description, $field, $actual_value, $new)
    {
        if (is_null($actual_value)) {
            $actual_value = ''; // Plug anomaly due to unusual corruption
        }

        $_actual_value = explode(' ', $actual_value, 2);
        if (!array_key_exists(1, $_actual_value)) {
            $_actual_value[1] = $_actual_value[0];
        }

        return form_input_page_link($_cf_name, $_cf_description, 'field_' . strval($field['id']), $_actual_value[0], $field['cf_required'] == 1, null, null, true, true);
    }

    /**
     * Find the posted value from the get_field_inputter field
     *
     * @param  boolean                  $editing Whether we were editing (because on edit, it could be a fractional edit)
     * @param  array                    $field The field details
     * @param  ?string                  $upload_dir Where the files will be uploaded to (null: do not store an upload, return NULL if we would need to do so)
     * @param  ?array                   $old_value Former value of field (null: none)
     * @return ?string                  The value (null: could not process)
     */
    public function inputted_to_field_value($editing, $field, $upload_dir = 'uploads/catalogues', $old_value = null)
    {
        $id = $field['id'];
        $tmp_name = 'field_' . strval($id);

        $value = post_param($tmp_name, $editing ? STRING_MAGIC_NULL : '');
        return $value;
    }
}
