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
 * @package    points
 */

/**
 * Module page class.
 */
class Module_leader_board
{
    /**
     * Find details of the module.
     *
     * @return ?array                   Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean                  $check_perms Whether to check permissions.
     * @param  ?MEMBER                  $member_id The member to check permissions as (null: current user).
     * @param  boolean                  $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean                  $be_deferential Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array                   A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        if ($GLOBALS['SITE_DB']->query_select_value('leader_board', 'COUNT(*)') == 0) {
            return array();
        }
        return array(
            '!' => array('POINT_LEADER_BOARD', 'menu/social/leader_board'),
        );
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @return ?tempcode                Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param('type', 'browse');

        require_lang('leader_board');

        $this->title = get_screen_title('POINT_LEADER_BOARD');

        return null;
    }

    /**
     * Execute the module.
     *
     * @return tempcode                 The result of execution.
     */
    public function run()
    {
        require_code('points');
        require_css('points');

        $start_date = intval(get_option('leader_board_start_date'));

        $start = get_param_integer('lb_start', 0);
        $max = get_param_integer('lb_max', 52);

        // Ensure the leader-board is getting calculated...
        require_code('leader_board');
        calculate_latest_leader_board(false);

        // Are there any rank images going to display?
        $or_list = '1=1';
        $admin_groups = $GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
        $moderator_groups = $GLOBALS['FORUM_DRIVER']->get_moderator_groups();
        foreach (array_merge($admin_groups, $moderator_groups) as $group_id) {
            $or_list .= ' AND id<>' . strval($group_id);
        }
        $has_rank_images = (get_forum_type() == 'ocf') && ($GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_groups WHERE ' . $or_list . ' AND ' . db_string_not_equal_to('g_rank_image', '')) != 0);

        // Continue on to displaying the leader-board...

        // Are there any rank images going to display?
        $or_list = '1=1';
        $admin_groups = $GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
        $moderator_groups = $GLOBALS['FORUM_DRIVER']->get_moderator_groups();
        foreach (array_merge($admin_groups, $moderator_groups) as $group_id) {
            $or_list .= ' AND id<>' . strval($group_id);
        }
        $has_rank_images = (get_forum_type() == 'ocf') && ($GLOBALS['FORUM_DB']->query_value('SELECT COUNT(*) FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_groups WHERE ' . $or_list . ' AND ' . db_string_not_equal_to('g_rank_image', '')) != 0);

        $weeks = $GLOBALS['SITE_DB']->query('SELECT DISTINCT date_and_time FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'leader_board WHERE date_and_time>=' . strval($start_date) . ' ORDER BY date_and_time DESC', $max, $start);
        if (count($weeks) == 0) {
            warn_exit(do_lang_tempcode('NO_ENTRIES'));
        }

        $num_weeks = $GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(DISTINCT date_and_time) FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'leader_board WHERE date_and_time>=' . strval($start_date));

        $first_week = $weeks[count($weeks) - 1]['date_and_time'];
        $weeks = collapse_1d_complexity('date_and_time', $weeks);
        $out = new Tempcode();
        foreach ($weeks as $week) {
            $rows = collapse_2d_complexity('lb_member', 'lb_points', $GLOBALS['SITE_DB']->query_select('leader_board', array('lb_member', 'lb_points'), array('date_and_time' => $week)));
            $week_tpl = new Tempcode();
            foreach ($rows as $member => $points) {
                $points_url = build_url(array('page' => 'points', 'type' => 'member', 'id' => $member), get_module_zone('points'));

                $profile_url = $GLOBALS['FORUM_DRIVER']->member_profile_url($member, false, true);

                $username = $GLOBALS['FORUM_DRIVER']->get_username($member);
                if (is_null($username)) {
                    $username = do_lang('UNKNOWN');
                }

                $week_tpl->attach(do_template('POINTS_LEADER_BOARD_ROW', array(
                    '_GUID' => '6d323b4b5abea0e82a14cb4745c4af4f',
                    'POINTS_URL' => $points_url,
                    'PROFILE_URL' => $profile_url,
                    'POINTS' => integer_format($points),
                    'USERNAME' => $username,
                    'ID' => strval($member),
                    'HAS_RANK_IMAGES' => $has_rank_images,
                )));
            }
            $nice_week = intval(($week - $first_week) / (7 * 24 * 60 * 60) + 1);
            $out->attach(do_template('POINTS_LEADER_BOARD_WEEK', array('_GUID' => '3a0f71bf20f9098e5711e85cf25f6549', 'WEEK' => integer_format($nice_week), 'ROWS' => $week_tpl)));
        }

        require_code('templates_pagination');
        $pagination = pagination(do_lang_tempcode('POINT_LEADER_BOARD'), $start, 'lb_start', $max, 'lb_max', $num_weeks);

        $tpl = do_template('POINTS_LEADER_BOARD_SCREEN', array('_GUID' => 'bab5f7b661435b83800532d3eebd0d54', 'TITLE' => $this->title, 'WEEKS' => $out, 'PAGINATION' => $pagination));

        require_code('templates_internalise_screen');
        return internalise_own_screen($tpl);
    }
}
