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
 * @package    chat
 */

/**
 * Get the number of people using the chat system at the moment. Note that this is intentionally different from 'users online' even if site wide IM is enabled- it has a 60 second timeout, so it really is active people.
 *
 * @return integer                      The number of people on the chat system
*/
function get_num_chatters()
{
    // We need to get all the messages that were posted in the last x minutes, and count them
    if (!defined('CHAT_ACTIVITY_PRUNE')) {
        define('CHAT_ACTIVITY_PRUNE',25);
    }
    return $GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(DISTINCT member_id) FROM ' . get_table_prefix() . 'chat_active a LEFT JOIN ' . get_table_prefix() . 'sessions s ON s.the_user=a.member_id WHERE session_invisible=0 AND date_and_time>=' . strval(time()-CHAT_ACTIVITY_PRUNE));
}

/**
 * Get the number of chatrooms in the database. By default, there is only one, but more may be added via the admin panel.
 *
 * @return  integer        The number of chatrooms in the database
*/
function get_num_chatrooms()
{
    return $GLOBALS['SITE_DB']->query_select_value('chat_rooms','COUNT(*)',array('is_im' => 0));
}

/**
 * Get the total number of chat posts in all the chatrooms.
 *
 * @return  integer        The number of chat posts in the database
*/
function get_num_chatposts()
{
    return $GLOBALS['SITE_DB']->query_select_value('chat_messages','COUNT(*)');
}
