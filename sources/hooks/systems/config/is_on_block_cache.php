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
 * @package    core_cleanup_tools
 */

/**
 * Hook class.
 */
class Hook_config_is_on_block_cache
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (null: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'BLOCK_CACHE',
            'type' => 'tick',
            'category' => 'PERFORMANCE',
            'group' => 'CACHES',
            'explanation' => 'CONFIG_OPTION_is_on_block_cache',
            'shared_hosting_restricted' => '1',
            'list_options' => '',

            'addon' => 'core_cleanup_tools',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string                  The default value (null: option is disabled)
     */
    public function get_default()
    {
        if (GOOGLE_APPENGINE) {
            return '1';
        }
        return $GLOBALS['SEMI_DEV_MODE'] ? '0' : '1';
    }
}
