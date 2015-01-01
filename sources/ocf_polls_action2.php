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
 * @package    core_ocf
 */

/**
 * Edit a forum poll.
 *
 * @param  AUTO_LINK                    $poll_id The ID of the poll we're editing.
 * @param  SHORT_TEXT                   $question The question.
 * @param  BINARY                       $is_private Whether the result tallies are kept private until the poll is made non-private.
 * @param  BINARY                       $is_open Whether the poll is open for voting.
 * @param  integer                      $minimum_selections The minimum number of selections that may be made.
 * @param  integer                      $maximum_selections The maximum number of selections that may be made.
 * @param  BINARY                       $requires_reply Whether members must have a post in the topic before they made vote.
 * @param  array                        $answers A list of the potential voteable answers.
 * @param  LONG_TEXT                    $reason The reason for editing the poll.
 * @return AUTO_LINK                    The ID of the topic the poll is on.
 */
function ocf_edit_poll($poll_id, $question, $is_private, $is_open, $minimum_selections, $maximum_selections, $requires_reply, $answers, $reason = '')
{
    require_code('ocf_polls');

    $topic_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('*'), array('t_poll_id' => $poll_id), '', 1);
    if (!ocf_may_edit_poll_by($topic_info[0]['t_forum_id'], $topic_info[0]['t_cache_first_member_id'])) {
        access_denied('I_ERROR');
    }
    $topic_id = $topic_info[0]['id'];
    $poll_info = $GLOBALS['FORUM_DB']->query_select('f_polls', array('*'), array('id' => $poll_id), '', 1);

    if ((!has_privilege(get_member(), 'may_unblind_own_poll')) && ($is_private == 0) && ($poll_info[0]['po_is_private'] == 1)) {
        access_denied('PRIVILEGE', 'may_unblind_own_poll');
    }

    $GLOBALS['FORUM_DB']->query_update('f_polls', array(
        'po_question' => $question,
        'po_is_private' => $is_private,
        'po_is_open' => $is_open,
        'po_minimum_selections' => $minimum_selections,
        'po_maximum_selections' => $maximum_selections,
        'po_requires_reply' => $requires_reply,
    ), array('id' => $poll_id), '', 1);

    $current_answers = $GLOBALS['FORUM_DB']->query_select('f_poll_answers', array('*'), array('pa_poll_id' => $poll_id));
    $total_after = count($answers);
    foreach ($current_answers as $i => $current_answer) {
        if ($i < $total_after) {
            $new_answer = $answers[$i];
            $update = array('pa_answer' => is_array($new_answer) ? $new_answer[0] : $new_answer);
            if (is_array($new_answer)) {
                $update['pa_cache_num_votes'] = $new_answer[1];
            }
            $GLOBALS['FORUM_DB']->query_update('f_poll_answers', $update, array('id' => $current_answer['id']), '', 1);
        } else {
            $GLOBALS['FORUM_DB']->query_delete('f_poll_answers', array('id' => $current_answer['id']), '', 1);
            $GLOBALS['FORUM_DB']->query_delete('f_poll_votes', array('pv_answer_id' => $current_answer['id']), '', 1);
        }
    }
    $i++;
    for (; $i < $total_after; $i++) {
        $new_answer = $answers[$i];
        $GLOBALS['FORUM_DB']->query_insert('f_poll_answers', array(
            'pa_poll_id' => $poll_id,
            'pa_answer' => is_array($new_answer) ? $new_answer[0] : $new_answer,
            'pa_cache_num_votes' => is_array($new_answer) ? $new_answer[1] : 0,
        ));
    }

    $name = $GLOBALS['FORUM_DB']->query_select_value('f_polls', 'po_question', array('id' => $poll_id));
    require_code('ocf_general_action2');
    ocf_mod_log_it('EDIT_TOPIC_POLL', strval($poll_id), $name, $reason);

    return $topic_id;
}

/**
 * Delete a forum poll.
 *
 * @param  AUTO_LINK                    $poll_id The ID of the poll we're deleting.
 * @param  LONG_TEXT                    $reason The reason for deleting the poll.
 * @param  boolean                      $check_perms Whether to check permissions.
 * @return AUTO_LINK                    The ID of the topic the poll is on.
 */
function ocf_delete_poll($poll_id, $reason = '', $check_perms = true)
{
    require_code('ocf_polls');

    $topic_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('*'), array('t_poll_id' => $poll_id), '', 1);
    if ($check_perms) {
        if (!ocf_may_delete_poll_by($topic_info[0]['t_forum_id'], $topic_info[0]['t_cache_first_member_id'])) {
            access_denied('I_ERROR');
        }
    }
    $topic_id = $topic_info[0]['id'];

    $name = $GLOBALS['FORUM_DB']->query_select_value('f_polls', 'po_question', array('id' => $poll_id));

    $GLOBALS['FORUM_DB']->query_delete('f_polls', array('id' => $poll_id), '', 1);
    $GLOBALS['FORUM_DB']->query_delete('f_poll_answers', array('pa_poll_id' => $poll_id));
    $GLOBALS['FORUM_DB']->query_delete('f_poll_votes', array('pv_poll_id' => $poll_id));

    $GLOBALS['FORUM_DB']->query_update('f_topics', array('t_poll_id' => null), array('t_poll_id' => $poll_id), '', 1);

    require_code('ocf_general_action2');
    ocf_mod_log_it('DELETE_TOPIC_POLL', strval($poll_id), $name, $reason);

    return $topic_id;
}

/**
 * Place a vote on a specified poll.
 *
 * @param  AUTO_LINK                    $poll_id The ID of the poll we're voting in.
 * @param  array                        $votes A list of poll answers that are being voted for.
 * @param  ?MEMBER                      $member_id The member that's voting (null: current member).
 * @param  ?array                       $topic_info The row of the topic the poll is for (null: get it from the DB).
 */
function ocf_vote_in_poll($poll_id, $votes, $member_id = null, $topic_info = null)
{
    // Who's voting
    if (is_null($member_id)) {
        $member_id = get_member();
    }

    // Check they're allowed to vote
    if (!has_privilege($member_id, 'vote_in_polls')) {
        warn_exit(do_lang_tempcode('VOTE_DENIED'));
    }
    if (is_null($topic_info)) {
        $topic_info = $GLOBALS['FORUM_DB']->query_select('f_topics', array('id', 't_forum_id'), array('t_poll_id' => $poll_id), '', 1);
    }
    if (!array_key_exists(0, $topic_info)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
    }
    $topic_id = $topic_info[0]['id'];
    $forum_id = $topic_info[0]['t_forum_id'];
    if ((!has_category_access($member_id, 'forums', strval($forum_id))) && (!is_null($forum_id))) {
        warn_exit(do_lang_tempcode('VOTE_CHEAT'));
    }
    $test = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_poll_votes', 'pv_member_id', array('pv_poll_id' => $poll_id, 'pv_member_id' => $member_id));
    if (is_guest($member_id)) {
        $voted_already_map = array('pv_poll_id' => $poll_id, 'pv_ip' => get_ip_address());
    } else {
        $voted_already_map = array('pv_poll_id' => $poll_id, 'pv_member_id' => $member_id);
    }
    $voted_already = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_poll_votes', 'pv_member_id', $voted_already_map);
    if (!is_null($voted_already)) {
        warn_exit(do_lang_tempcode('NOVOTE'));
    }
    if (!is_null($test)) {
        warn_exit(do_lang_tempcode('NOVOTE'));
    }

    // Check their vote is valid
    $rows = $GLOBALS['FORUM_DB']->query_select('f_polls', array('po_is_open', 'po_minimum_selections', 'po_maximum_selections', 'po_requires_reply'), array('id' => $poll_id), '', 1);
    if (!array_key_exists(0, $rows)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
    }
    if ((count($votes) < $rows[0]['po_minimum_selections'])
        || (count($votes) > $rows[0]['po_maximum_selections']) || ($rows[0]['po_is_open'] == 0)
    ) {
        warn_exit(do_lang_tempcode('VOTE_CHEAT'));
    }
    $answers = collapse_1d_complexity('id', $GLOBALS['FORUM_DB']->query_select('f_poll_answers', array('id'), array('pa_poll_id' => $poll_id)));
    if (($rows[0]['po_requires_reply'] == 1) && (!ocf_has_replied_topic($topic_id, $member_id))) {
        warn_exit(do_lang_tempcode('POLL_REQUIRES_REPLY'));
    }

    foreach ($votes as $vote) {
        if (!in_array($vote, $answers)) {
            warn_exit(do_lang_tempcode('VOTE_CHEAT'));
        }

        $GLOBALS['FORUM_DB']->query_insert('f_poll_votes', array(
            'pv_poll_id' => $poll_id,
            'pv_member_id' => $member_id,
            'pv_answer_id' => $vote,
            'pv_ip' => get_ip_address(),
        ));

        $GLOBALS['FORUM_DB']->query('UPDATE ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_poll_answers SET pa_cache_num_votes=(pa_cache_num_votes+1) WHERE id=' . strval($vote), 1);
    }
    $GLOBALS['FORUM_DB']->query('UPDATE ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_polls SET po_cache_total_votes=(po_cache_total_votes+1) WHERE id=' . strval($poll_id), 1);
}
