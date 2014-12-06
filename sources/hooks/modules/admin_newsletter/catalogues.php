<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    catalogues
 */

/**
 * Hook class.
 */
class Hook_whatsnew_catalogues
{
    /**
     * Find selectable (filterable) categories.
     *
     * @param  TIME                     $updated_since The time that there must be entries found newer than
     * @return ?array                   Tuple of result details: HTML list of all types that can be choosed, title for selection list (null: disabled)
     */
    public function choose_categories($updated_since)
    {
        if (!addon_installed('catalogues')) {
            return null;
        }

        require_lang('catalogues');

        require_code('catalogues');
        $cats = create_selection_list_catalogues(null, true, false, $updated_since);
        return array($cats, do_lang('CATALOGUE_ENTRIES'));
    }

    /**
     * Run function for newsletter hooks.
     *
     * @param  TIME                     $cutoff_time The time that the entries found must be newer than
     * @param  LANGUAGE_NAME            $lang The language the entries found must be in
     * @param  string                   $filter Category filter to apply
     * @return array                    Tuple of result details
     */
    public function run($cutoff_time, $lang, $filter)
    {
        if (!module_installed('catalogues')) {
            return array();
        }

        require_lang('catalogues');

        require_code('fields');

        $max = intval(get_option('max_newsletter_whatsnew'));

        $new = new Tempcode();

        require_code('ocfiltering');
        $or_list = ocfilter_to_sqlfragment($filter, 'c_name', null, null, null, null, false);

        $privacy_join = '';
        $privacy_where = '';
        if (addon_installed('content_privacy')) {
            require_code('content_privacy');
            list($privacy_join, $privacy_where) = get_privacy_where_clause('catalogue_entry', 'r', $GLOBALS['FORUM_DRIVER']->get_guest_id());
        }

        $rows = $GLOBALS['SITE_DB']->query('SELECT cc_id,id,ce_submitter FROM ' . get_table_prefix() . 'catalogue_entries r' . $privacy_join . ' WHERE ce_validated=1 AND ce_add_date>' . strval($cutoff_time) . ' AND (' . $or_list . ')' . $privacy_where . ' ORDER BY ce_add_date DESC', $max);

        if (count($rows) == $max) {
            return array();
        }

        foreach ($rows as $row) {
            $id = $row['id'];

            $c_name = $GLOBALS['SITE_DB']->query_select_value_if_there('catalogue_categories', 'c_name', array('id' => $row['cc_id']));
            if (is_null($c_name)) {
                continue; // Corruption
            }
            $c_title = $GLOBALS['SITE_DB']->query_select_value('catalogues', 'c_title', array('c_name' => $c_name));

            $fields = $GLOBALS['SITE_DB']->query_select('catalogue_fields', array('id', 'cf_type'), array('c_name' => $c_name), 'ORDER BY cf_order');

            // Work out name
            $name = '';
            $ob = get_fields_hook($fields[0]['cf_type']);
            list(, , $raw_type) = $ob->get_field_value_row_bits($fields[0]);
            switch ($raw_type) {
                case 'short_trans':
                    $_name = $GLOBALS['SITE_DB']->query_select_value_if_there('catalogue_efv_short_trans', 'cv_value', array('ce_id' => $row['id'], 'cf_id' => $fields[0]['id']));
                    if (is_null($name)) {
                        $name = do_lang('UNKNOWN');
                    } else {
                        $name = get_translated_text($_name, null, $lang);
                    }
                    break;
                case 'short_text':
                    $name = $GLOBALS['SITE_DB']->query_select_value_if_there('catalogue_efv_short', 'cv_value', array('ce_id' => $row['id'], 'cf_id' => $fields[0]['id']));
                    if (is_null($name)) {
                        $name = do_lang('UNKNOWN');
                    }
                    break;
                case 'float':
                    $_name = $GLOBALS['SITE_DB']->query_select_value_if_there('catalogue_efv_float', 'cv_value', array('ce_id' => $row['id'], 'cf_id' => $fields[0]['id']));
                    if (is_null($name)) {
                        $name = do_lang('UNKNOWN');
                    } else {
                        $name = float_to_raw_string($_name);
                    }
                    break;
                case 'integer':
                    $_name = $GLOBALS['SITE_DB']->query_select_value_if_there('catalogue_efv_integer', 'cv_value', array('ce_id' => $row['id'], 'cf_id' => $fields[0]['id']));
                    if (is_null($name)) {
                        $name = do_lang('UNKNOWN');
                    } else {
                        $name = strval($_name);
                    }
                    break;
            }

            // Work out thumbnail
            $thumbnail = mixed();
            foreach ($fields as $field) {
                if ($field['cf_type'] == 'picture') {
                    $thumbnail = $GLOBALS['SITE_DB']->query_select_value('catalogue_efv_short', 'cv_value', array('ce_id' => $row['id'], 'cf_id' => $field['id']));
                    if ($thumbnail != '') {
                        if (url_is_local($thumbnail)) {
                            $thumbnail = get_custom_base_url() . '/' . $thumbnail;
                        }
                    } else {
                        $thumbnail = mixed();
                    }
                }
            }

            $_url = build_url(array('page' => 'catalogues', 'type' => 'entry', 'id' => $row['id']), get_module_zone('catalogues'), null, false, false, true);
            $url = $_url->evaluate();

            $catalogue = get_translated_text($c_title, null, $lang);

            $member_id = (is_guest($row['ce_submitter'])) ? null : strval($row['ce_submitter']);

            $new->attach(do_template('NEWSLETTER_WHATSNEW_RESOURCE_FCOMCODE', array('_GUID' => '4ae604e5d0e9cf4d28e7d811dc4558e5', 'MEMBER_ID' => $member_id, 'URL' => $url, 'CATALOGUE' => $catalogue, 'NAME' => $name, 'THUMBNAIL' => $thumbnail, 'CONTENT_TYPE' => 'catalogue_entry', 'CONTENT_ID' => strval($id)), null, false, null, '.txt', 'text'));

            handle_has_checked_recently($url); // We know it works, so mark it valid so as to not waste CPU checking within the generated Comcode
        }

        return array($new, do_lang('CATALOGUE_ENTRIES', '', '', '', $lang));
    }
}
