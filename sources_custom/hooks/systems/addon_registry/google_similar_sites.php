<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    google_similar_sites
 */

class Hook_addon_registry_google_similar_sites
{
    /**
     * Get a list of file permissions to set
     *
     * @return array                    File permissions to set
     */
    public function get_chmod_array()
    {
        return array();
    }

    /**
     * Get the version of ocPortal this addon is for
     *
     * @return float                    Version number
     */
    public function get_version()
    {
        return ocp_version_number();
    }

    /**
     * Get the addon category
     *
     * @return string                   The category
     */
    public function get_category()
    {
        return 'Information Display';
    }

    /**
     * Get the addon author
     *
     * @return string                   The author
     */
    public function get_author()
    {
        return 'Kamen Blaginov';
    }

    /**
     * Find other authors
     *
     * @return array                    A list of co-authors that should be attributed
     */
    public function get_copyright_attribution()
    {
        return array();
    }

    /**
     * Get the addon licence (one-line summary only)
     *
     * @return string                   The licence
     */
    public function get_licence()
    {
        return 'Licensed on the same terms as ocPortal';
    }

    /**
     * Get the description of the addon
     *
     * @return string                   Description of the addon
     */
    public function get_description()
    {
        return 'Display sites which are similar to a specified URL which you put in the parameter box. Example:
[code=\"Comcode\"][block criteria=\"http://www.example.com\" max=\"5\"]side_similar_sites[/block][/code]';
    }

    /**
     * Get a list of tutorials that apply to this addon
     *
     * @return array                    List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
        );
    }

    /**
     * Get a mapping of dependency types
     *
     * @return array                    File permissions to set
     */
    public function get_dependencies()
    {
        return array(
            'requires' => array(
                'PHP 5.2',
            ),
            'recommends' => array(
            ),
            'conflicts_with' => array(
            )
        );
    }

    /**
     * Explicitly say which icon should be used
     *
     * @return URLPATH                  Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/48x48/menu/_generic_admin/component.png';
    }

    /**
     * Get a list of files that belong to this addon
     *
     * @return array                    List of files
     */
    public function get_file_list()
    {
        return array(
            'sources_custom/hooks/systems/addon_registry/google_similar_sites.php',
            'sources_custom/blocks/side_similar_sites.php',
            'lang_custom/EN/similar_sites.ini',
            'themes/default/templates_custom/BLOCK_SIDE_SIMILAR_SITES.tpl',
        );
    }
}
