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
 * @package    custom_comcode
 */

class Hook_addon_registry_custom_comcode
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
     * Get the description of the addon
     *
     * @return string                   Description of the addon
     */
    public function get_description()
    {
        return 'Create new Comcode tags.';
    }

    /**
     * Get a list of tutorials that apply to this addon
     *
     * @return array                    List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_adv_comcode',
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
            'requires' => array(),
            'recommends' => array(),
            'conflicts_with' => array(),
            'previously_in_addon' => array(
                'core_page_management'
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
        return 'themes/default/images/icons/48x48/menu/adminzone/setup/custom_comcode.png';
    }

    /**
     * Get a list of files that belong to this addon
     *
     * @return array                    List of files
     */
    public function get_file_list()
    {
        return array(
            'themes/default/images/icons/24x24/menu/adminzone/setup/custom_comcode.png',
            'themes/default/images/icons/48x48/menu/adminzone/setup/custom_comcode.png',
            'sources/hooks/systems/resource_meta_aware/custom_comcode_tag.php',
            'sources/custom_comcode.php',
            'sources/hooks/systems/snippets/exists_tag.php',
            'sources/hooks/systems/preview/custom_comcode.php',
            'sources/hooks/systems/meta/comcode_page.php',
            'sources/hooks/systems/addon_registry/custom_comcode.php',
            'adminzone/pages/modules/admin_custom_comcode.php',
            'themes/default/templates/BLOCK_MAIN_CUSTOM_COMCODE_TAGS.tpl',
            'themes/default/templates/CUSTOM_COMCODE_TAG_ROW.tpl',
            'lang/EN/custom_comcode.ini',
            'sources/blocks/main_custom_comcode_tags.php',
            'sources/hooks/systems/page_groupings/custom_comcode.php',
            'sources/hooks/blocks/main_custom_gfx/index.html',
            'sources/hooks/blocks/main_custom_gfx/text_overlay.php',
            'sources/hooks/blocks/main_custom_gfx/.htaccess',
            'themes/default/images/button1.png',
            'themes/default/images/button2.png',
            'sources/blocks/main_custom_gfx.php',
            'sources/hooks/blocks/main_custom_gfx/rollover_button.php',
            'sources/hooks/systems/comcode/.htaccess',
            'sources/hooks/systems/comcode/index.html',
            'sources/hooks/systems/occle_fs/custom_comcode_tags.php',
        );
    }


    /**
     * Get mapping between template names and the method of this class that can render a preview of them
     *
     * @return array                    The mapping
     */
    public function tpl_previews()
    {
        return array(
            'CUSTOM_COMCODE_TAG_ROW.tpl' => 'block_main_custom_comcode_tags',
            'BLOCK_MAIN_CUSTOM_COMCODE_TAGS.tpl' => 'block_main_custom_comcode_tags'
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array                    Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__block_main_custom_comcode_tags()
    {
        $content = new ocp_tempcode();
        foreach (placeholder_array() as $tag) {
            $content->attach(do_lorem_template('CUSTOM_COMCODE_TAG_ROW',array(
                'TITLE' => lorem_word(),
                'DESCRIPTION' => lorem_paragraph(),
                'EXAMPLE' => lorem_word(),
            )));
        }

        return array(
            lorem_globalise(do_lorem_template('BLOCK_MAIN_CUSTOM_COMCODE_TAGS',array(
                'TAGS' => $content,
            )),null,'',true)
        );
    }
}
