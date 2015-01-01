<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    iotds
 */

require_code('crud_module');

/**
 * Module page class.
 */
class Module_cms_iotds extends Standard_crud_module
{
    public $lang_type = 'IOTD';
    public $special_edit_frontend = true;
    public $archive_entry_point = '_SEARCH:iotds:browse';
    public $view_entry_point = '_SEARCH:iotds:view:_ID';
    public $user_facing = true;
    public $send_validation_request = false;
    public $upload = 'image';
    public $permissions_require = 'mid';
    public $menu_label = 'IOTDS';
    public $table = 'iotd';

    public $title;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @param  boolean                  $top_level Whether this is running at the top level, prior to having sub-objects called.
     * @param  ?ID_TEXT                 $type The screen type to consider for meta-data purposes (null: read from environment).
     * @return ?tempcode                Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run($top_level = true, $type = null)
    {
        $type = get_param('type', 'browse');

        require_lang('iotds');

        set_helper_panel_tutorial('tut_featured');

        if ($type == 'edit') {
            $this->title = get_screen_title('EDIT_OR_CHOOSE_IOTD');
        }

        if ($type == '_choose') {
            $this->title = get_screen_title('CHOOSE_IOTD');
        }

        if ($type == '_delete') {
            $this->title = get_screen_title('DELETE_IOTD');
        }

        return parent::pre_run($top_level);
    }

    /**
     * Standard crud_module run_start.
     *
     * @param  ID_TEXT                  $type The type of module execution
     * @return tempcode                 The output of the run
     */
    public function run_start($type)
    {
        require_code('iotds');
        require_code('iotds2');
        require_css('iotds');

        $this->add_one_label = do_lang_tempcode('ADD_IOTD');
        $this->edit_one_label = do_lang_tempcode('EDIT_OR_CHOOSE_IOTD');
        $this->edit_this_label = do_lang_tempcode('EDIT_THIS_IOTD');

        if ($type == 'browse') {
            return $this->browse();
        }
        if ($type == '_choose') {
            return $this->set_iotd();
        }
        if ($type == '_delete') {
            $this->delete_actualisation(post_param_integer('id'));
            return $this->do_next_manager($this->title, do_lang_tempcode('SUCCESS'), null);
        }

        return new Tempcode();
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean                  $check_perms Whether to check permissions.
     * @param  ?MEMBER                  $member_id The member to check permissions as (null: current user).
     * @param  boolean                  $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean                  $be_deferential Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array                   A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        return array(
            'browse' => array('MANAGE_IOTDS', 'menu/rich_content/iotds'),
        ) + parent::get_entry_points();
    }

    /**
     * Find privileges defined as overridable by this module.
     *
     * @return array                    A map of privileges that are overridable; privilege to 0 or 1. 0 means "not category overridable". 1 means "category overridable".
     */
    public function get_privilege_overrides()
    {
        require_lang('iotds');
        return array('submit_midrange_content' => array(0, 'ADD_IOTD'), 'bypass_validation_midrange_content' => array(0, 'BYPASS_VALIDATION_IOTD'), 'edit_own_midrange_content' => array(0, 'EDIT_OWN_IOTD'), 'edit_midrange_content' => array(0, 'EDIT_IOTD'), 'delete_own_midrange_content' => array(0, 'DELETE_OWN_IOTD'), 'delete_midrange_content' => array(0, 'DELETE_IOTD'), 'edit_own_highrange_content' => array(0, 'EDIT_OWN_LIVE_IOTD'), 'edit_highrange_content' => array(0, 'EDIT_LIVE_IOTD'), 'delete_own_highrange_content' => array(0, 'DELETE_OWN_LIVE_IOTD'), 'delete_highrange_content' => array(0, 'DELETE_LIVE_IOTD'));
    }

    /**
     * The do-next manager for before content management.
     *
     * @return tempcode                 The UI
     */
    public function browse()
    {
        require_code('templates_donext');
        return do_next_manager(get_screen_title('MANAGE_IOTDS'), comcode_lang_string('DOC_IOTDS'),
            array(
                has_privilege(get_member(), 'submit_midrange_content', 'cms_iotds') ? array('menu/_generic_admin/add_one', array('_SELF', array('type' => 'add'), '_SELF'), do_lang('ADD_IOTD')) : null,
                has_privilege(get_member(), 'edit_own_midrange_content', 'cms_iotds') ? array('menu/_generic_admin/edit_one', array('_SELF', array('type' => 'edit'), '_SELF'), do_lang('EDIT_OR_CHOOSE_IOTD')) : null,
            ),
            do_lang('MANAGE_IOTDS')
        );
    }

    /**
     * Get tempcode for an IOTD adding/editing form.
     *
     * @param  ?AUTO_LINK               $id The IOTD ID (null: new)
     * @param  URLPATH                  $url The URL to the image
     * @param  URLPATH                  $thumb_url The URL to the thumbnail
     * @param  SHORT_TEXT               $title The title
     * @param  LONG_TEXT                $caption The caption
     * @param  boolean                  $current Whether the IOTD is/will-be currently active
     * @param  ?BINARY                  $allow_rating Whether rating is allowed (null: decide statistically, based on existing choices)
     * @param  ?SHORT_INTEGER           $allow_comments Whether comments are allowed (0=no, 1=yes, 2=review style) (null: decide statistically, based on existing choices)
     * @param  ?BINARY                  $allow_trackbacks Whether trackbacks are allowed (null: decide statistically, based on existing choices)
     * @param  LONG_TEXT                $notes Notes for the IOTD
     * @return array                    A pair: the tempcode for the visible fields, and the tempcode for the hidden fields
     */
    public function get_form_fields($id = null, $url = '', $thumb_url = '', $title = '', $caption = '', $current = false, $allow_rating = 1, $allow_comments = 1, $allow_trackbacks = 1, $notes = '')
    {
        list($allow_rating, $allow_comments, $allow_trackbacks) = $this->choose_feedback_fields_statistically($allow_rating, $allow_comments, $allow_trackbacks);

        $fields = new Tempcode();
        $hidden = new Tempcode();
        require_code('form_templates');
        handle_max_file_size($hidden, 'image');
        $fields->attach(form_input_line_comcode(do_lang_tempcode('TITLE'), do_lang_tempcode('DESCRIPTION_TITLE'), 'title', $title, true));

        $set_name = 'image';
        $required = true;
        $set_title = do_lang_tempcode('IMAGE');
        $field_set = alternate_fields_set__start($set_name);

        $field_set->attach(form_input_upload(do_lang_tempcode('UPLOAD'), '', 'file', false, null, null, true, str_replace(' ', '', get_option('valid_images'))));

        $field_set->attach(form_input_url(do_lang_tempcode('URL'), '', 'url', $url, false));

        $fields->attach(alternate_fields_set__end($set_name, $set_title, '', $field_set, $required, $url));

        if (!function_exists('imagetypes')) {
            $thumb_width = get_option('thumb_width');

            $set_name = 'thumbnail';
            $required = true;
            $set_title = do_lang_tempcode('THUMBNAIL');
            $field_set = alternate_fields_set__start($set_name);

            $field_set->attach(form_input_upload(do_lang_tempcode('UPLOAD'), '', 'file2', false, null, null, true, str_replace(' ', '', get_option('valid_images'))));

            $field_set->attach(form_input_url(do_lang_tempcode('URL'), '', 'thumb_url', $thumb_url, false));

            $fields->attach(alternate_fields_set__end($set_name, $set_title, do_lang_tempcode('DESCRIPTION_THUMBNAIL', escape_html($thumb_width)), $field_set, $required, $thumb_url));
        }
        $fields->attach(form_input_text_comcode(do_lang_tempcode('CAPTION'), do_lang_tempcode('DESCRIPTION_DESCRIPTION'), 'caption', $caption, false));
        if (has_privilege(get_member(), 'choose_iotd')) {
            if ($caption == '') {
                $test = $GLOBALS['SITE_DB']->query_select_value_if_there('iotd', 'is_current', array('is_current' => 1));
                if (is_null($test)) {
                    $current = true;
                }
            }
            $fields->attach(form_input_tick(do_lang_tempcode('IMMEDIATE_USE'), do_lang_tempcode('DESCRIPTION_IMMEDIATE_USE'), 'validated', $current));
        }

        // Meta data
        require_code('feedback2');
        $feedback_fields = feedback_fields($allow_rating == 1, $allow_comments == 1, $allow_trackbacks == 1, false, $notes, $allow_comments == 2, false, true, false);
        $fields->attach(meta_data_get_fields('iotd', is_null($id) ? null : strval($id), false, null, ($feedback_fields->is_empty()) ? META_DATA_HEADER_YES : META_DATA_HEADER_FORCE));
        $fields->attach($feedback_fields);

        if (addon_installed('content_reviews')) {
            $fields->attach(content_review_get_fields('iotd', is_null($id) ? null : strval($id)));
        }

        return array($fields, $hidden);
    }

    /**
     * The UI to manage the IOTD.
     *
     * @return tempcode                 The UI
     */
    public function edit()
    {
        $count = $GLOBALS['SITE_DB']->query_select_value('iotd', 'COUNT(*)');
        if ($count == 0) {
            inform_exit(do_lang_tempcode('NO_ENTRIES'));
        }

        $used = get_param_integer('used', 0);

        $only_owned = has_privilege(get_member(), 'edit_midrange_content', 'cms_iotds') ? null : get_member();

        $current_iotd = $this->_get_iotd_boxes(1, 1);
        $unused_iotd = $this->_get_iotd_boxes(0, 0, $only_owned);
        $used_iotd = new Tempcode();
        if ($used == 1) {
            $used_iotd = $this->_get_iotd_boxes(1);
        }
        $used_url = build_url(array('page' => '_SELF', 'type' => 'edit', 'used' => 1), '_SELF');

        $search_url = build_url(array('page' => 'search', 'id' => 'iotds'), get_module_zone('search'));
        $archive_url = build_url(array('page' => 'iotds'), get_module_zone('iotds'));
        $text = paragraph(do_lang_tempcode('CHOOSE_EDIT_LIST_EXTRA', escape_html($search_url->evaluate()), escape_html($archive_url->evaluate())));

        return do_template('IOTD_ADMIN_CHOOSE_SCREEN', array('_GUID' => '3ee2847c986bf349caa40d462f45eb9c', 'SHOWING_OLD' => $used == 1, 'TITLE' => $this->title, 'TEXT' => $text, 'USED_URL' => $used_url, 'CURRENT_IOTD' => $current_iotd, 'UNUSED_IOTD' => $unused_iotd, 'USED_IOTD' => $used_iotd));
    }

    /**
     * Get an interface for choosing an IOTD.
     *
     * @param  BINARY                   $used Whether to show used IOTDs
     * @param  BINARY                   $current Whether to show the current IOTD
     * @param  ?MEMBER                  $submitter The member to only show iotds submitted-by (null: do not filter)
     * @return tempcode                 The UI
     */
    public function _get_iotd_boxes($used = 0, $current = 0, $submitter = null)
    {
        $where = array('used' => $used, 'is_current' => $current);
        if (!is_null($submitter)) {
            $where['submitter'] = $submitter;
        }
        $rows = $GLOBALS['SITE_DB']->query_select('iotd', array('*'), $where, 'ORDER BY id DESC', 100);
        if (count($rows) == 100) { // Ah, too much, then we should pick a better set
            $rows = $GLOBALS['SITE_DB']->query_select('iotd', array('*'), $where, 'ORDER BY add_date DESC', 100);
        }
        $previews = new Tempcode();
        foreach ($rows as $myrow) {
            require_code('iotds');
            $previews->attach(render_iotd_box($myrow, '_SEARCH', true, false));
        }
        if (($previews->is_empty()) && ($current == 1)) {
            return new Tempcode();
        }

        return $previews;
    }

    /**
     * Standard crud_module submitter getter.
     *
     * @param  ID_TEXT                  $id The entry for which the submitter is sought
     * @return array                    The submitter, and the time of submission (null submission time implies no known submission time)
     */
    public function get_submitter($id)
    {
        $rows = $GLOBALS['SITE_DB']->query_select('iotd', array('submitter', 'add_date'), array('id' => intval($id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return array(null, null);
        }
        return array($rows[0]['submitter'], $rows[0]['add_date']);
    }

    /**
     * Standard crud_module edit form filler.
     *
     * @param  ID_TEXT                  $id The entry being edited
     * @return array                    A pair: the tempcode for the visible fields, and the tempcode for the hidden fields
     */
    public function fill_in_edit_form($id)
    {
        $rows = $GLOBALS['SITE_DB']->query_select('iotd', array('*'), array('id' => intval($id)));
        if (!array_key_exists(0, $rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $myrow = $rows[0];

        $caption = get_translated_text($myrow['caption']);
        $title = get_translated_text($myrow['i_title']);

        check_edit_permission(($myrow['is_current'] == 1) ? 'high' : 'mid', $myrow['submitter']);

        return $this->get_form_fields($myrow['url'], $myrow['thumb_url'], $title, $caption, $myrow['is_current'], $myrow['allow_rating'], $myrow['allow_comments'], $myrow['allow_trackbacks'], $myrow['notes']);
    }

    /**
     * Standard crud_module add actualiser.
     *
     * @return ID_TEXT                  The entry added
     */
    public function add_actualisation()
    {
        require_code('uploads');

        $urls = get_url('url', 'file', 'uploads/iotds', 0, OCP_UPLOAD_IMAGE, true, 'thumb_url', 'file2');

        if (($urls[0] == '') || ($urls[1] == '')) {
            warn_exit(do_lang_tempcode('IMPROPERLY_FILLED_IN_UPLOAD'));
        }

        if ((substr($urls[0], 0, 8) != 'uploads/') && (is_null(http_download_file($urls[0], 0, false))) && (!is_null($GLOBALS['HTTP_MESSAGE_B']))) {
            attach_message($GLOBALS['HTTP_MESSAGE_B'], 'warn');
        }

        $url = $urls[0];
        $thumb_url = $urls[1];

        $title = post_param('title');
        $caption = post_param('caption');
        $allow_rating = post_param_integer('allow_rating', 0);
        $allow_comments = post_param_integer('allow_comments', 0);
        $notes = post_param('notes', '');
        $allow_trackbacks = post_param_integer('allow_trackbacks', 0);
        $validated = post_param_integer('validated', 0);

        $meta_data = actual_meta_data_get_fields('iotd', null);

        $id = add_iotd($url, $title, $caption, $thumb_url, $validated, $allow_rating, $allow_comments, $allow_trackbacks, $notes, $meta_data['add_time'], $meta_data['submitter'], 0, null, $meta_data['views']);

        if (($validated == 1) || (!addon_installed('unvalidated'))) {
            if (has_actual_page_access(get_modal_user(), 'iotds')) {
                require_code('activities');
                syndicate_described_activity('iotds:ACTIVITY_ADD_IOTD', $title, '', '', '_SEARCH:iotds:view:' . strval($id), '', '', 'iotds');
            }
        }

        $current = post_param_integer('validated', 0);
        if ($current == 1) {
            if (!has_privilege(get_member(), 'choose_iotd')) {
                log_hack_attack_and_exit('BYPASS_VALIDATION_HACK');
            }

            set_iotd($id);
        }

        if (addon_installed('content_reviews')) {
            content_review_set('iotd', strval($id));
        }

        return strval($id);
    }

    /**
     * Standard crud_module edit actualiser.
     *
     * @param  ID_TEXT                  $_id The entry being edited
     */
    public function edit_actualisation($_id)
    {
        $id = intval($_id);

        $rows = $GLOBALS['SITE_DB']->query_select('iotd', array('is_current', 'submitter'), array('id' => $id), '', 1);
        if (!array_key_exists(0, $rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $is_current = $rows[0]['is_current'];
        $submitter = $rows[0]['submitter'];

        require_code('uploads');

        check_edit_permission(($is_current == 1) ? 'high' : 'mid', $submitter);

        $urls = get_url('url', 'file', 'uploads/iotds', 0, OCP_UPLOAD_IMAGE, true, 'thumb_url', 'file2');

        if (($urls[0] == '') || ($urls[1] == '')) {
            warn_exit(do_lang_tempcode('IMPROPERLY_FILLED_IN_UPLOAD'));
        }

        $url = $urls[0];
        $thumb_url = $urls[1];

        if ((substr($urls[0], 0, 8) != 'uploads/') && ($urls[0] != '') && (is_null(http_download_file($urls[0], 0, false))) && (!is_null($GLOBALS['HTTP_MESSAGE_B']))) {
            attach_message($GLOBALS['HTTP_MESSAGE_B'], 'warn');
        }

        $allow_rating = post_param_integer('allow_rating', 0);
        $allow_comments = post_param_integer('allow_comments', 0);
        $notes = post_param('notes', '');
        $allow_trackbacks = post_param_integer('allow_trackbacks', 0);

        $current = post_param_integer('validated', 0);
        $title = post_param('title');

        if (($current == 1) && ($GLOBALS['SITE_DB']->query_select_value('iotd', 'is_current', array('id' => $id)) == 0)) { // Just became validated, syndicate as just added
            $submitter = $GLOBALS['SITE_DB']->query_select_value('iotd', 'submitter', array('id' => $id));

            if (has_actual_page_access(get_modal_user(), 'iotds')) {
                require_code('activities');
                syndicate_described_activity('iotds:ACTIVITY_ADD_IOTD', $title, '', '', '_SEARCH:iotds:view:' . strval($id), '', '', 'iotds', 1, null/*$submitter*/);
            }
        }

        $meta_data = actual_meta_data_get_fields('iotd', strval($id));

        edit_iotd($id, $title, post_param('caption'), $thumb_url, $url, $allow_rating, $allow_comments, $allow_trackbacks, $notes, $meta_data['edit_time'], $meta_data['add_time'], $meta_data['views'], $meta_data['submitter'], true);

        if ($current == 1) {
            if ($is_current == 0) {
                if (!has_privilege(get_member(), 'choose_iotd')) {
                    log_hack_attack_and_exit('BYPASS_VALIDATION_HACK');
                }

                set_iotd($id);
            }
        }

        if (addon_installed('content_reviews')) {
            content_review_set('iotd', strval($id));
        }
    }

    /**
     * The actualiser to set the IOTD.
     *
     * @return tempcode                 The UI
     */
    public function set_iotd()
    {
        check_privilege('choose_iotd');

        $id = post_param_integer('id');

        set_iotd($id);

        return $this->do_next_manager($this->title, do_lang_tempcode('SUCCESS'), $id);
    }

    /**
     * Standard crud_module delete actualiser.
     *
     * @param  ID_TEXT                  $_id The entry being deleted
     */
    public function delete_actualisation($_id)
    {
        $id = intval($_id);

        $rows = $GLOBALS['SITE_DB']->query_select('iotd', array('is_current', 'submitter'), array('id' => $id));
        if (!array_key_exists(0, $rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $is_current = $rows[0]['is_current'];
        $submitter = $rows[0]['submitter'];
        check_delete_permission(($is_current == 1) ? 'high' : 'mid', $submitter);

        delete_iotd(intval($id));
    }
}
