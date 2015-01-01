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
 * @package    wordfilter
 */

/**
 * Hook class.
 */
class Hook_sw_wordfilter
{
    /**
     * Run function for features in the setup wizard.
     *
     * @return array                    Current settings.
     */
    public function get_current_settings()
    {
        $settings = array();
        $settings['have_default_wordfilter'] = ($GLOBALS['SITE_DB']->query_select_value('wordfilter', 'COUNT(*)') == 0) ? '0' : '1';
        return $settings;
    }

    /**
     * Run function for features in the setup wizard.
     *
     * @param  array                    $field_defaults Default values for the fields, from the install-profile.
     * @return tempcode                 An input field.
     */
    public function get_fields($field_defaults)
    {
        $current_settings = $this->get_current_settings();
        $field_defaults += $current_settings; // $field_defaults will take precedence, due to how "+" operator works in PHP

        require_lang('wordfilter');
        $fields = new Tempcode();
        if ($current_settings['have_default_wordfilter'] == '1') {
            $fields->attach(form_input_tick(do_lang_tempcode('KEEP_WORD_FILTER'), do_lang_tempcode('DESCRIPTION_HAVE_DEFAULT_WORDFILTER'), 'have_default_wordfilter', $field_defaults['have_default_wordfilter'] == '1'));
        }
        return $fields;
    }

    /**
     * Run function for setting features from the setup wizard.
     */
    public function set_fields()
    {
        if (post_param_integer('have_default_wordfilter', 0) == 0) {
            $GLOBALS['SITE_DB']->query_delete('wordfilter');
        }
    }
}
