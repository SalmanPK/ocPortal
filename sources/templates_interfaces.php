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
 * @package    core_abstract_interfaces
 */

/**
 * Get tempcode for cropped text, that fully reveals itself on mouse-over.
 *
 * @param  string                       The text
 * @param  integer                      The length to crop at
 * @return tempcode                     The cropped text
 */
function tpl_crop_text_mouse_over($text,$len)
{
    if (strlen($text)>$len) {
        $text2 = escape_html(substr($text,0,$len-2)) . '&hellip;';
        return do_template('CROP_TEXT_MOUSE_OVER',array('_GUID' => '5e6bb4853dd4bc2064e999b6820a3088','TEXT_LARGE' => escape_html($text),'TEXT_SMALL' => $text2));
    }
    return make_string_tempcode(escape_html($text));
}
