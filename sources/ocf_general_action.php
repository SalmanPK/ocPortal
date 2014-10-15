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

/**
 * Make a post template.
 *
 * @param  SHORT_TEXT                   The title for the template.
 * @param  LONG_TEXT                    The text of the template.
 * @param  SHORT_TEXT                   The multi code specifying which forums this is applicable in.
 * @param  BINARY                       Whether to use this as the default post in applicable forum.
 * @return AUTO_LINK                    The added ID
 */
function ocf_make_post_template($title,$text,$forum_multi_code,$use_default_forums)
{
    require_code('global4');
    prevent_double_submit('ADD_POST_TEMPLATE',null,$title);

    $id = $GLOBALS['FORUM_DB']->query_insert('f_post_templates',array(
        't_title' => $title,
        't_text' => $text,
        't_forum_multi_code' => $forum_multi_code,
        't_use_default_forums' => $use_default_forums
    ),true);

    log_it('ADD_POST_TEMPLATE',strval($id),$title);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resourcefs_moniker('post_template',strval($id),null,null,true);
    }

    return $id;
}

/**
 * Make an emoticon.
 *
 * @param  SHORT_TEXT                   The textual code entered to make the emoticon appear.
 * @param  ID_TEXT                      The image code used for the emoticon.
 * @param  integer                      The relevance level. 0 is core, shown immediately.
 * @range  0 4
 * @param  BINARY                       Whether this may be used as a topic emoticon.
 * @param  BINARY                       Whether this may only be used by privileged members
 */
function ocf_make_emoticon($code,$theme_img_code,$relevance_level = 1,$use_topics = 1,$is_special = 0)
{
    $test = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_emoticons','e_code',array('e_code' => $code));
    if (!is_null($test)) {
        warn_exit(do_lang_tempcode('CONFLICTING_EMOTICON_CODE',escape_html($test)));
    }

    $GLOBALS['FORUM_DB']->query_insert('f_emoticons',array(
        'e_code' => $code,
        'e_theme_img_code' => $theme_img_code,
        'e_relevance_level' => $relevance_level,
        'e_use_topics' => $use_topics,
        'e_is_special' => $is_special
    ));

    log_it('ADD_EMOTICON',$code,$theme_img_code);
}

/**
 * Make a Welcome E-mail.
 *
 * @param  SHORT_TEXT                   A name for the Welcome E-mail
 * @param  SHORT_TEXT                   The subject of the Welcome E-mail
 * @param  LONG_TEXT                    The message body of the Welcome E-mail
 * @param  integer                      The number of hours before sending the e-mail
 * @param  ?AUTO_LINK                   What newsletter to send out to instead of members (NULL: none)
 * @param  ?AUTO_LINK                   The usergroup to tie to (NULL: none)
 * @param  ID_TEXT                      How to send regarding usergroups (blank: indiscriminately)
 * @set primary secondary ""
 * @return AUTO_LINK                    The ID
 */
function ocf_make_welcome_email($name,$subject,$text,$send_time,$newsletter = null,$usergroup = null,$usergroup_type = '')
{
    require_code('global4');
    prevent_double_submit('ADD_WELCOME_EMAIL',null,$subject);

    $map = array(
        'w_name' => $name,
        'w_newsletter' => $newsletter,
        'w_send_time' => $send_time,
        'w_usergroup' => $usergroup,
        'w_usergroup_type' => $usergroup_type,
    );
    $map += insert_lang('w_subject',$subject,2);
    $map += insert_lang('w_text',$text,2);
    $id = $GLOBALS['SITE_DB']->query_insert('f_welcome_emails',$map,true);
    log_it('ADD_WELCOME_EMAIL',strval($id),$subject);
    return $id;
}
