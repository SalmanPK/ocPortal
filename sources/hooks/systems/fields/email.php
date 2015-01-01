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
class Hook_fields_email
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
        if ($required !== null) {
            if (($required) && ($default == '')) {
                $default = $GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member());
            }
        }
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
        if ($ev == '') {
            return new Tempcode();
        }
        return do_template('HYPERLINK_EMAIL', array('_GUID' => 'f074c9a299fb3b1836a5a76270378666', 'VALUE' => $ev));
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
        if (($field['cf_default'] == '!') && ($actual_value == '')) {
            $actual_value = $GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member());
        }
        return form_input_email($_cf_name, $_cf_description, 'field_' . strval($field['id']), $actual_value, $field['cf_required'] == 1);
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
        require_code('type_validation');
        $value = post_param($tmp_name, $editing ? STRING_MAGIC_NULL : '');
        if (($value != '') && ($value != STRING_MAGIC_NULL) && (!is_valid_email_address($value))) {
            warn_exit(do_lang_tempcode('INVALID_EMAIL_ADDRESS'));
        }
        return $value;
    }
}
