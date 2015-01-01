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
 * @package    backup
 */

/**
 * Module page class.
 */
class Module_admin_backup
{
    /**
     * Find details of the module.
     *
     * @return ?array                   Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 3;
        $info['locked'] = false;
        return $info;
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
            'browse' => array('BACKUP', 'menu/adminzone/tools/bulk_content_actions/backups'),
        );
    }

    /**
     * Uninstall the module.
     */
    public function uninstall()
    {
        delete_value('last_backup');
        delete_value('backup_max_size');
        delete_value('backup_schedule_time');
        delete_value('backup_recurrance_days');
        delete_value('backup_max_size');
        delete_value('backup_b_type');

        //require_code('files');
        //deldir_contents(get_custom_file_base().'/exports/backups',true);
    }

    /**
     * Install the module.
     *
     * @param  ?integer                 $upgrade_from What version we're upgrading from (null: new install)
     * @param  ?integer                 $upgrade_from_hack What hack version we're upgrading from (null: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
    }

    private $title;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @return ?tempcode                Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param('type', 'browse');

        require_lang('backups');

        set_helper_panel_tutorial('tut_backup');
        set_helper_panel_text(comcode_lang_string('DOC_BACKUPS_2'));

        if ($type == 'make_backup') {
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('BACKUP'))));
            breadcrumb_set_self(do_lang_tempcode('START'));
        }

        if ($type == 'confirm_delete') {
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('BACKUP'))));
            breadcrumb_set_self(do_lang_tempcode('DELETE'));
        }

        if ($type == 'browse' || $type == 'make_backup') {
            $this->title = get_screen_title('BACKUP');
        }

        if ($type == 'confirm_delete' || $type == 'delete') {
            $this->title = get_screen_title('DELETE');
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return tempcode                 The result of execution.
     */
    public function run()
    {
        appengine_general_guard();

        require_code('files');

        if (!is_null($GLOBALS['CURRENT_SHARE_USER'])) {
            warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));
        }

        decache('main_staff_checklist');

        require_code('backup');

        $type = get_param('type', 'browse');

        if ($type == 'browse') {
            return $this->backup_interface();
        }
        if ($type == 'make_backup') {
            return $this->make_backup();
        }
        if ($type == 'confirm_delete') {
            return $this->confirm_delete();
        }
        if ($type == 'delete') {
            return $this->delete();
        }

        return new Tempcode();
    }

    /**
     * The UI to do a backup.
     *
     * @return tempcode                 The UI
     */
    public function backup_interface()
    {
        require_javascript('ajax');

        $last_backup = intval(get_value('last_backup'));
        if ($last_backup == 0) {
            $text = do_lang_tempcode('NO_LAST_BACKUP');
        } elseif (date('Y/m/d', utctime_to_usertime($last_backup)) == date('Y/m/d', utctime_to_usertime())) {
            $text = do_lang_tempcode('LAST_BACKUP_TODAY');
        } elseif (date('Y/m/d', utctime_to_usertime($last_backup)) == date('Y/m/d', utctime_to_usertime(time() - 60 * 60 * 24))) {
            $text = do_lang_tempcode('LAST_BACKUP_YESTERDAY');
        } else {
            $text = do_lang_tempcode('LAST_BACKUP',
                integer_format(
                    intval(round(
                        (time() - $last_backup) / (60 * 60 * 24)
                    ))
                )
            );
        }

        $url = build_url(array('page' => '_SELF', 'type' => 'make_backup'), '_SELF');

        $max_size = intval(get_value('backup_max_size'));
        if ($max_size == 0) {
            $max_size = 100;
        }

        require_code('form_templates');
        $content = new Tempcode();
        $content->attach(form_input_radio_entry('b_type', 'full', true, do_lang_tempcode('FULL_BACKUP')));
        $content->attach(form_input_radio_entry('b_type', 'incremental', false, do_lang_tempcode('INCREMENTAL_BACKUP')));
        $content->attach(form_input_radio_entry('b_type', 'sql', false, do_lang_tempcode('SQL_BACKUP')));
        $fields = form_input_radio(do_lang_tempcode('TYPE'), do_lang_tempcode('BACKUP_TYPE'), 'b_type', $content);
        $fields->attach(form_input_integer(do_lang_tempcode('MAXIMUM_SIZE_INCLUSION'), do_lang_tempcode('MAX_FILE_SIZE'), 'max_size', $max_size, false));
        if (addon_installed('calendar')) {
            $fields->attach(form_input_date__scheduler(do_lang_tempcode('SCHEDULE_TIME'), do_lang_tempcode('DESCRIPTION_SCHEDULE_TIME'), 'schedule', false, true, true));
            $_recurrence_days = get_value('backup_recurrance_days');
            $recurrance_days = is_null($_recurrence_days) ? null : intval($_recurrence_days);
            if (cron_installed()) {
                $fields->attach(form_input_integer(do_lang_tempcode('RECURRANCE_DAYS'), do_lang_tempcode('DESCRIPTION_RECURRANCE_DAYS'), 'recurrance_days', $recurrance_days, false));
            }
        }

        $javascript = '';
        if (addon_installed('calendar')) {
            if (cron_installed()) {
                $javascript = 'var d_ob=[document.getElementById(\'schedule_day\'),document.getElementById(\'schedule_month\'),document.getElementById(\'schedule_year\'),document.getElementById(\'schedule_hour\'),document.getElementById(\'schedule_minute\')]; var hide_func=function() { document.getElementById(\'recurrance_days\').disabled=((d_ob[0].selectedIndex+d_ob[1].selectedIndex+d_ob[2].selectedIndex+d_ob[3].selectedIndex+d_ob[4].selectedIndex)>0); }; d_ob[0].onchange=hide_func; d_ob[1].onchange=hide_func; d_ob[2].onchange=hide_func; d_ob[3].onchange=hide_func; d_ob[4].onchange=hide_func; hide_func();';
            }
        }

        $form = do_template('FORM', array('_GUID' => '64ae569b2cce398e89d1b4167f116193', 'HIDDEN' => '', 'JAVASCRIPT' => $javascript, 'TEXT' => '', 'FIELDS' => $fields, 'SUBMIT_ICON' => 'buttons__proceed', 'SUBMIT_NAME' => do_lang_tempcode('BACKUP'), 'URL' => $url));

        $results = $this->get_results();

        return do_template('BACKUP_LAUNCH_SCREEN', array('_GUID' => '26a82a0627632db79b35055598de5d23', 'TITLE' => $this->title, 'TEXT' => $text, 'RESULTS' => $results, 'FORM' => $form));
    }

    /**
     * Helper function to find information about past backups.
     *
     * @return tempcode                 The UI
     */
    public function get_results()
    {
        // Find all files in the incoming directory
        $path = get_custom_file_base() . '/exports/backups/';
        if (!file_exists($path)) {
            require_code('files2');
            make_missing_directory($path);
        }
        $handle = opendir($path);
        $entries = array();
        while (false !== ($file = readdir($handle))) {
            if ((!is_dir($path . $file)) && ((get_file_extension($file) == 'tar') || (get_file_extension($file) == 'txt') || (get_file_extension($file) == 'gz') || (get_file_extension($file) == '')) && (is_file($path . $file))) {
                $entries[] = array('file' => $file, 'size' => filesize($path . $file), 'mtime' => filemtime($path . $file));
            }
        }
        closedir($handle);
        sort_maps_by($entries, 'mtime');

        if (count($entries) != 0) {
            require_code('templates_columned_table');
            $header_row = columned_table_header_row(array(do_lang_tempcode('FILENAME'), do_lang_tempcode('TYPE'), do_lang_tempcode('SIZE'), do_lang_tempcode('DATE_TIME'), new Tempcode()));

            $rows = new Tempcode();
            foreach ($entries as $entry) {
                $delete_url = build_url(array('page' => '_SELF', 'type' => 'confirm_delete', 'file' => $entry['file']), '_SELF');
                $link = get_custom_base_url() . '/exports/backups/' . $entry['file'];

                $actions = do_template('COLUMNED_TABLE_ACTION_DELETE_ENTRY', array('_GUID' => '23a8b5d5d345d8fdecc74b01fe5a9042', 'NAME' => $entry['file'], 'URL' => $delete_url));

                $type = do_lang_tempcode('UNKNOWN');
                switch (get_file_extension($entry['file'])) {
                    case 'gz':
                        $type = do_lang_tempcode('BACKUP_FILE_COMPRESSED');
                        break;
                    case 'tar':
                        $type = do_lang_tempcode('BACKUP_FILE_UNCOMPRESSED');
                        break;
                    case 'txt':
                        $type = do_lang_tempcode('BACKUP_FILE_LOG');
                        break;
                    case '':
                        $type = do_lang_tempcode('BACKUP_FILE_UNFINISHED');
                        break;
                }

                $rows->attach(columned_table_row(array(hyperlink($link, escape_html($entry['file'])), $type, clean_file_size($entry['size']), get_timezoned_date($entry['mtime']), $actions)));
            }

            $files = do_template('COLUMNED_TABLE', array('_GUID' => '726070efa71843236e975d87d4a17dae', 'HEADER_ROW' => $header_row, 'ROWS' => $rows));
        } else {
            $files = new Tempcode();
        }

        return $files;
    }

    /**
     * The actualiser to start a backup.
     *
     * @return tempcode                 The UI
     */
    public function make_backup()
    {
        $b_type = post_param('b_type', 'full');
        if ($b_type == 'full') {
            $file = 'Backup_full_' . date('Y-m-d', utctime_to_usertime()) . '__' . uniqid('', true); // The last bit is unfortunate, but we need to stop URL guessing
        } elseif ($b_type == 'incremental') {
            $file = 'Backup_incremental' . date('Y-m-d', utctime_to_usertime()) . '__' . uniqid('', true); // The last bit is unfortunate, but we need to stop URL guessing
        } elseif ($b_type == 'sql') {
            $file = 'Backup_database' . date('Y-m-d', utctime_to_usertime()) . '__' . uniqid('', true); // The last bit is unfortunate, but we need to stop URL guessing
        } else {
            warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }

        $max_size = post_param_integer('max_size', 0);
        if (($max_size == 0) || (!is_numeric($max_size))) {
            $max_size = 1000000000;
        }

        if (addon_installed('calendar')) {
            $schedule = get_input_date('schedule');
            if (!is_null($schedule)) {
                set_value('backup_schedule_time', strval($schedule));
                set_value('backup_recurrance_days', strval(post_param_integer('recurrance_days', 0)));
                set_value('backup_max_size', strval($max_size));
                set_value('backup_b_type', $b_type);
                return inform_screen($this->title, do_lang_tempcode('SUCCESSFULLY_SCHEDULED_BACKUP'));
            }
        }

        require_code('tasks');
        $ret = call_user_func_array__long_task(do_lang('BACKUP'), $this->title, 'make_backup', array($file, $b_type, $max_size));

        $url = build_url(array('page' => '_SELF'), '_SELF');
        redirect_screen($this->title, $url, do_lang_tempcode('BACKUP_INFO_1', $file));
        return new Tempcode();
    }

    /**
     * The UI to confirm deletion of a backup file.
     *
     * @return tempcode                 The UI
     */
    public function confirm_delete()
    {
        $file = get_param('file');

        $preview = do_lang_tempcode('CONFIRM_DELETE', escape_html($file));
        $url = build_url(array('page' => '_SELF', 'type' => 'delete'), '_SELF');

        $fields = form_input_hidden('file', $file);

        return do_template('CONFIRM_SCREEN', array('_GUID' => 'fa69bb63385525921c75954c03a3aa43', 'TITLE' => $this->title, 'PREVIEW' => $preview, 'URL' => $url, 'FIELDS' => $fields));
    }

    /**
     * The actualiser to delete a backup file.
     *
     * @return tempcode                 The UI
     */
    public function delete()
    {
        $file = post_param('file');

        $path = get_custom_file_base() . '/exports/backups/' . filter_naughty($file);
        if (!@unlink($path)) {
            warn_exit(do_lang_tempcode('WRITE_ERROR', escape_html($path)));
        }
        sync_file('exports/backups/' . $file);

        // Show it worked / Refresh
        $url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }
}
