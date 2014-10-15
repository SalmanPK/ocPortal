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
 * @package    stats_block
 */

class Hook_config_activity_show_stats_count_page_views_this_month
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (NULL: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'PAGE_VIEWS_THIS_MONTH',
            'type' => 'tick',
            'category' => 'BLOCKS',
            'group' => 'STATISTICS',
            'explanation' => 'CONFIG_OPTION_activity_show_stats_count_page_views_this_month',
            'shared_hosting_restricted' => '0',
            'list_options' => '',

            'addon' => 'stats_block',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string                  The default value (NULL: option is disabled)
     */
    public function get_default()
    {
        return '0';
    }
}
