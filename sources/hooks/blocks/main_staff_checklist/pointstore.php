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
 * @package    pointstore
 */

class Hook_checklist_pointstore
{
    /**
     * Find items to include on the staff checklist.
     *
     * @return array                    An array of tuples: The task row to show, the number of seconds until it is due (or NULL if not on a timer), the number of things to sort out (or NULL if not on a queue), The name of the config option that controls the schedule (or NULL if no option).
     */
    public function run()
    {
        if (!addon_installed('pointstore')) {
            return array();
        }

        // Pointstore maintenance
        require_lang('pointstore');

        $status = do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM_STATUS_NA');
        $url = build_url(array('page' => 'admin_pointstore'),'adminzone');
        $tpl = do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM',array('_GUID' => 'f9ec73adbee71411a915004d9d052cfd','URL' => '','STATUS' => $status,'TASK' => urlise_lang(do_lang('NAG_POINTSTORE'),$url),'INFO' => ''));
        return array(array($tpl,null,null,null));
    }
}
