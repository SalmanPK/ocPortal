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
 * @package    chat
 */

/**
 * Hook class.
 */
class Hook_ocf_cpf_filter_points_chat
{
    /**
     * Find which special CPFs to enable.
     *
     * @return array                    A list of CPFs to enable
     */
    public function to_enable()
    {
        return array();//Actually, hide all these  array('points_gained_chat'=>1);
    }
}
