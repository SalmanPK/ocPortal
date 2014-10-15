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
 * @package    staff
 */

class Hook_page_groupings_staff
{
    /**
     * Run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
     *
     * @param  ?MEMBER                  Member ID to run as (NULL: current member)
     * @param  boolean                  Whether to use extensive documentation tooltips, rather than short summaries
     * @return array                    List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
     */
    public function run($member_id = null,$extensive_docs = false)
    {
        if (!addon_installed('staff')) {
            return array();
        }

        return array(
            array('security','menu/site_meta/staff',array('admin_staff',array('type' => 'misc'),get_module_zone('admin_staff')),do_lang_tempcode('staff:STAFF'),'staff:DOC_STAFF'),
            array('site_meta','menu/site_meta/staff',array('staff',array(),get_module_zone('staff')),do_lang_tempcode('staff:STAFF')),
        );
    }
}
