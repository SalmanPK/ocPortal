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
class Hook_snippet_im_friends_rejig
{
    /**
     * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
     *
     * @return tempcode                 The snippet
     */
    public function run()
    {
        require_code('chat_lobby');

        $member_id = get_param_integer('member_id', get_member());
        if (!is_guest($member_id)) {
            enforce_personal_access($member_id);
        }

        $simpler = (get_param_integer('simpler', 0) == 1);

        $max = get_param_integer('max', intval(get_option('max_chat_lobby_friends')));

        // Do an add action?
        $add = post_param('add');
        $add_member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_username($add);
        if ((!is_null($add_member_id)) && (!is_guest($add_member_id))) {
            if ($add_member_id != get_member()) {
                if (is_null($GLOBALS['SITE_DB']->query_select_value_if_there('chat_friends', 'date_and_time', array('member_likes' => get_member(), 'member_liked' => $add_member_id)))) {
                    require_code('chat2');
                    friend_add(get_member(), $add_member_id);
                }
            }
        }

        return show_im_contacts($member_id, $simpler, $max);
    }
}
