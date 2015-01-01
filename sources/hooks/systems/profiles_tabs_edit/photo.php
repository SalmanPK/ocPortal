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
 * @package    ocf_member_photos
 */

/**
 * Hook class.
 */
class Hook_profiles_tabs_edit_photo
{
    /**
     * Find whether this hook is active.
     *
     * @param  MEMBER                   $member_id_of The ID of the member who is being viewed
     * @param  MEMBER                   $member_id_viewing The ID of the member who is doing the viewing
     * @return boolean                  Whether this hook is active
     */
    public function is_active($member_id_of, $member_id_viewing)
    {
        return (($member_id_of == $member_id_viewing) || (has_privilege($member_id_viewing, 'assume_any_member')) || (has_privilege($member_id_viewing, 'member_maintenance')));
    }

    /**
     * Render function for profile tabs edit hooks.
     *
     * @param  MEMBER                   $member_id_of The ID of the member who is being viewed
     * @param  MEMBER                   $member_id_viewing The ID of the member who is doing the viewing
     * @param  boolean                  $leave_to_ajax_if_possible Whether to leave the tab contents NULL, if tis hook supports it, so that AJAX can load it later
     * @return ?array                   A tuple: The tab title, the tab body text (may be blank), the tab fields, extra JavaScript (may be blank) the suggested tab order, hidden fields (optional) (null: if $leave_to_ajax_if_possible was set), the icon
     */
    public function render_tab($member_id_of, $member_id_viewing, $leave_to_ajax_if_possible = false)
    {
        $title = do_lang_tempcode('PHOTO');

        $order = 30;

        // Special delete actualliser
        if (post_param_integer('delete_photo', 0) == 1) {
            require_code('ocf_members_action');
            require_code('ocf_members_action2');
            ocf_member_choose_photo_concrete('', '', $member_id_of);

            attach_message(do_lang_tempcode('SUCCESS_SAVE'), 'inform');
        } // Actualiser
        elseif (post_param_integer('submitting_photo_tab', 0) == 1) {
            require_code('ocf_members_action');
            require_code('ocf_members_action2');
            ocf_member_choose_photo('photo_url', 'photo_file', $member_id_of);

            attach_message(do_lang_tempcode('SUCCESS_SAVE'), 'inform');
        }

        if ($leave_to_ajax_if_possible) {
            return null;
        }

        $photo_url = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of, 'm_photo_url');
        $thumb_url = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of, 'm_photo_thumb_url');

        // UI fields
        $fields = new Tempcode();
        require_code('form_templates');

        $set_name = 'photo';
        $required = false;
        $set_title = do_lang_tempcode('PHOTO');
        $field_set = alternate_fields_set__start($set_name);

        $field_set->attach(form_input_upload(do_lang_tempcode('UPLOAD'), '', 'photo_file', false, null, null, true, str_replace(' ', '', get_option('valid_images'))));

        $field_set->attach(form_input_url(do_lang_tempcode('URL'), '', 'photo_url', $photo_url, false));

        $fields->attach(alternate_fields_set__end($set_name, $set_title, '', $field_set, $required, null, function_exists('imagetypes')));

        if (!function_exists('imagetypes')) {
            $thumb_width = get_option('thumb_width');

            $set_name = 'thumbnail';
            $required = false;
            $set_title = do_lang_tempcode('THUMBNAIL');
            $field_set = alternate_fields_set__start($set_name);

            $field_set->attach(form_input_upload(do_lang_tempcode('THUMBNAIL'), '', 'photo_file2', false, null, null, true, str_replace(' ', '', get_option('valid_images'))));

            $field_set->attach(form_input_url(do_lang_tempcode('URL'), '', 'photo_thumb_url', $thumb_url, false));

            $fields->attach(alternate_fields_set__end($set_name, $set_title, do_lang_tempcode('DESCRIPTION_THUMBNAIL', escape_html($thumb_width)), $field_set, $required));
        }

        $hidden = new Tempcode();
        handle_max_file_size($hidden, 'image');
        $hidden->attach(form_input_hidden('submitting_photo_tab', '1'));

        $text = new Tempcode();
        require_code('images');
        $max = floatval(get_max_image_size()) / floatval(1024 * 1024);
        if ($max < 3.0) {
            require_code('files2');
            $config_url = get_upload_limit_config_url();
            $text->attach(paragraph(do_lang_tempcode(is_null($config_url) ? 'MAXIMUM_UPLOAD' : 'MAXIMUM_UPLOAD_STAFF', escape_html(($max > 10.0) ? integer_format(intval($max)) : float_format($max)), is_null($config_url) ? '' : escape_html($config_url))));
        }

        $text = do_template('OCF_EDIT_PHOTO_TAB', array('_GUID' => 'ae0eb6d27bc8b576b326b54a9a792554', 'TEXT' => $text, 'MEMBER_ID' => strval($member_id_of), 'USERNAME' => $GLOBALS['FORUM_DRIVER']->get_username($member_id_of), 'PHOTO' => $GLOBALS['FORUM_DRIVER']->get_member_photo_url($member_id_of)));

        $javascript = '';

        return array($title, $fields, $text, $javascript, $order, $hidden, 'tabs/member_account/edit/photo', function_exists('imagetypes'));
    }
}
