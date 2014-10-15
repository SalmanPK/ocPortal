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
 * @package    core_ocf
 */

class Hook_Notification_ocf_password_changed extends Hook_Notification
{
    /**
     * Get a list of all the notification codes this hook can handle.
     * (Addons can define hooks that handle whole sets of codes, so hooks are written so they can take wide authority)
     *
     * @return array                    List of codes (mapping between code names, and a pair: section and labelling for those codes)
     */
    public function list_handled_codes()
    {
        $list = array();
        $list['ocf_password_changed'] = array(do_lang('MEMBERS'),do_lang('ocf:NOTIFICATION_TYPE_ocf_password_changed'));
        return $list;
    }
}
