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
 * @package    setupwizard
 */

/**
 * Hook class.
 */
class Hook_admin_setupwizard_installprofiles_community
{
    /**
     * Get info about the installprofile
     *
     * @return array                    Map of installprofile details
     */
    public function info()
    {
        return array(
            'title' => do_lang('FORUM_SLASH_COMMUNITY'),
        );
    }

    /**
     * Get a list of addons that are kept with this installation profile (added to the list of addons always kept)
     *
     * @return array                    Pair: List of addons in the profile, Separated list of ones to show under advanced
     */
    public function get_addon_list()
    {
        return array(
            array('facebook_support'/*this will be downloaded as it is not bundled*/, 'ocf_forum', 'points', 'pointstore', 'ocf_thematic_avatars', 'ocf_cartoon_avatars', 'calendar', 'chat', 'polls', 'users_online_block', 'forum_blocks', 'polls', 'newsletter'),
            array());
    }

    /**
     * Get a map of default settings associated with this installation profile
     *
     * @return array                    Map of default settings
     */
    public function field_defaults()
    {
        return array(
            'have_default_banners_hosting' => '0',
            'have_default_banners_donation' => '1',
            'have_default_banners_advertising' => '1',
            'have_default_catalogues_projects' => '0',
            'have_default_catalogues_faqs' => '1',
            'have_default_catalogues_links' => '1',
            'have_default_catalogues_contacts' => '0',
            'keep_personal_galleries' => '1',
            'keep_news_categories' => '1',
            'have_default_rank_set' => '1',
            'show_content_tagging' => '0',
            'show_content_tagging_inline' => '0',
            'show_screen_actions' => '0',
            'rules' => 'liberal',
        );
    }

    /**
     * Find details of desired blocks
     *
     * @return array                    Details of what blocks are wanted
     */
    public function default_blocks()
    {
        return array(
            'YES' => array(
                'main_greeting',
                'main_forum_news',
                'main_leader_board',
                'main_forum_topics',
                'main_quotes',
            ),
            'YES_CELL' => array(
                'main_content',
                'main_poll',
            ),
            'PANEL_LEFT' => array(),
            'PANEL_RIGHT' => array(
                    'side_users_online',
                    'side_stats',
                    'side_calendar',
                    'side_shoutbox',
                ) + ((get_option('sitewide_im') == '1') ? array('side_friends') : array()),
        );
    }

    /**
     * Get options for blocks in this profile
     *
     * @return array                    Details of what block options are wanted
     */
    public function block_options()
    {
        return array();
    }

    /**
     * Execute any special code needed to put this install profile into play
     */
    public function install_code()
    {
    }
}
