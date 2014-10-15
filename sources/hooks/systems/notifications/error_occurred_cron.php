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
 * @package    core
 */

class Hook_Notification_error_occurred_cron extends Hook_Notification__Staff
{
    /**
     * Find the initial setting that members have for a notification code (only applies to the member_could_potentially_enable members).
     *
     * @param  ID_TEXT                  Notification code
     * @param  ?SHORT_TEXT              The category within the notification code (NULL: none)
     * @return integer                  Initial setting
     */
    public function get_initial_setting($notification_code,$category = null)
    {
        return A_NA;
    }

    /**
     * Get a list of all the notification codes this hook can handle.
     * (Addons can define hooks that handle whole sets of codes, so hooks are written so they can take wide authority)
     *
     * @return array                    List of codes (mapping between code names, and a pair: section and labelling for those codes)
     */
    public function list_handled_codes()
    {
        $list = array();
        $list['error_occurred_cron'] = array(do_lang('ERRORS'),do_lang('NOTIFICATION_TYPE_error_occurred_cron'));
        return $list;
    }
}
