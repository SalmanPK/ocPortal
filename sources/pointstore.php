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

/**
 * Check to see if the specified e-mail address has already been purchased. If so, spawn an error message.
 *
 * @param  ID_TEXT                      The prefix (mailbox name)
 * @param  ID_TEXT                      The suffix (domain name)
 */
function pointstore_handle_error_taken($prefix,$suffix)
{
    // Has this email address been taken?
    $taken = $GLOBALS['SITE_DB']->query_select_value_if_there('sales','details',array('details' => $prefix,'details2' => '@' . $suffix));
    if (!is_null($taken)) {
        warn_exit(do_lang_tempcode('EMAIL_TAKEN'));
    }
}

/**
 * Get a tempcode list of the available mail domains.
 *
 * @param  ID_TEXT                      The type of mail domain
 * @set    pop3 forw
 * @param  integer                      Description
 * @return tempcode                     The tempcode list of available domains
 */
function get_mail_domains($type,$points_left)
{
    $rows = $GLOBALS['SITE_DB']->query('SELECT * FROM ' . get_table_prefix() . 'prices WHERE name LIKE \'' . db_encode_like($type . '%') . '\'');
    $list = new ocp_tempcode();
    foreach ($rows as $row) {
        $address = substr($row['name'],strlen($type));

        //If we can't afford the mail, turn the text red
        $red = ($points_left<$row['price']);

        $list->attach(form_input_list_entry($address,false,'@' . $address . ' ' . do_lang('PRICE_GIVE',integer_format($row['price'])),$red));
    }
    return $list;
}

/**
 * Check to see if the member already has an account of this type. If so, an error message is shown, as you can only own of each type.
 *
 * @param  ID_TEXT                      The type of mail domain
 * @set    pop3 forw
 */
function pointstore_handle_error_already_has($type)
{
    $userid = get_member();

    // If we already own a forwarding account, inform our users.
    $has_one_already = $GLOBALS['SITE_DB']->query_select_value_if_there('sales','memberid',array('memberid' => $userid,'purchasetype' => $type));
    if (!is_null($has_one_already)) {
        warn_exit(do_lang_tempcode('ALREADY_HAVE'));
    }
}
