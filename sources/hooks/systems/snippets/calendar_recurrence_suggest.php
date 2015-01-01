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
 * @package    calendar
 */

/**
 * Hook class.
 */
class Hook_snippet_calendar_recurrence_suggest
{
    /**
     * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
     *
     * @return tempcode                 The snippet
     */
    public function run()
    {
        require_code('calendar');

        $day_of_month = get_param_integer('day');
        $month = get_param_integer('month');
        $year = get_param_integer('year');
        $hour = get_param_integer('hour');
        $minute = get_param_integer('minute');
        $do_timezone_conv = get_param_integer('do_timezone_conv');
        $all_day_event = get_param_integer('all_day_event');

        $default_monthly_spec_type = get_param('monthly_spec_type');

        return monthly_spec_type_chooser($day_of_month, $month, $year, $default_monthly_spec_type);
    }
}
