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
 * @package    ocf_member_photos
 */

/**
 * Hook class.
 */
class Hook_addon_registry_ocf_member_photos
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
        return 'Member photos.';
    }

    /**
     * Get a list of tutorials that apply to this addon
     *
     * @return array                    List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_members',
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
        );
    }

    /**
     * Explicitly say which icon should be used
     *
     * @return URLPATH                  Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/48x48/tabs/member_account/edit/photo.png';
    }

    /**
     * Get a list of files that belong to this addon
     *
     * @return array                    List of files
     */
    public function get_file_list()
    {
        return array(
            'themes/default/images/icons/24x24/tabs/member_account/edit/photo.png',
            'themes/default/images/icons/48x48/tabs/member_account/edit/photo.png',
            'sources/hooks/systems/addon_registry/ocf_member_photos.php',
            'uploads/ocf_photos/index.html',
            'uploads/ocf_photos_thumbs/index.html',
            'uploads/ocf_photos/.htaccess',
            'uploads/ocf_photos_thumbs/.htaccess',
            'sources/hooks/systems/profiles_tabs_edit/photo.php',
            'sources/hooks/systems/notifications/ocf_choose_photo.php',
            'themes/default/templates/OCF_EDIT_PHOTO_TAB.tpl',
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
            'templates/OCF_EDIT_PHOTO_TAB.tpl' => 'ocf_edit_photo_tab'
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array                    Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__ocf_edit_photo_tab()
    {
        require_lang('ocf');
        require_css('ocf');

        return array(
            lorem_globalise(do_lorem_template('OCF_EDIT_PHOTO_TAB', array(
                'USERNAME' => lorem_word(),
                'PHOTO' => placeholder_image_url(),
                'TEXT' => '',
            )), null, '', true)
        );
    }
}
