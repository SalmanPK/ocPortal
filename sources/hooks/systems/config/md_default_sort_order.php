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
 * Hook class.
 */
class Hook_config_md_default_sort_order
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'MD_DEFAULT_SORT_ORDER',
            'type' => 'list',
            'category' => 'USERS',
            'group' => 'MEMBER_DIRECTORY',
            'explanation' => 'CONFIG_OPTION_md_default_sort_order',
            'shared_hosting_restricted' => '0',
            'list_options' => 'm_username ASC|m_cache_num_posts DESC|m_join_time ASC|m_join_time DESC|m_last_visit_time DESC',

            'addon' => 'core_ocf',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string                  The default value (null: option is disabled)
     */
    public function get_default()
    {
        return 'm_join_time DESC';
    }
}
