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
 * @package    core_rich_media
 */

/**
 * Hook class.
 */
class Hook_media_rendering_code
{
    /**
     * Get the label for this media rendering type.
     *
     * @return string                   The label
     */
    public function get_type_label()
    {
        require_lang('comcode');
        return do_lang('MEDIA_TYPE_' . preg_replace('#^Hook_media_rendering_#', '', __CLASS__));
    }

    /**
     * Find the media types this hook serves.
     *
     * @return integer                  The media type(s), as a bitmask
     */
    public function get_media_type()
    {
        return MEDIA_TYPE_OTHER;
    }

    /**
     * See if we can recognise this mime type.
     *
     * @param  ID_TEXT                  $mime_type The mime type
     * @return integer                  Recognition precedence
     */
    public function recognises_mime_type($mime_type)
    {
        return MEDIA_RECOG_PRECEDENCE_TRIVIAL;
    }

    /**
     * See if we can recognise this URL pattern.
     *
     * @param  URLPATH                  $url URL to pattern match
     * @return integer                  Recognition precedence
     */
    public function recognises_url($url)
    {
        return MEDIA_RECOG_PRECEDENCE_TRIVIAL;
    }

    /**
     * Provide code to display what is at the URL, in the most appropriate way.
     *
     * @param  mixed                    $url URL to render
     * @param  mixed                    $url_safe URL to render (no sessions etc)
     * @param  array                    $attributes Attributes (e.g. width, height, length)
     * @param  boolean                  $as_admin Whether there are admin privileges, to render dangerous media types
     * @param  ?MEMBER                  $source_member Member to run as (null: current member)
     * @return tempcode                 Rendered version
     */
    public function render($url, $url_safe, $attributes, $as_admin = false, $source_member = null)
    {
        if (is_object($url)) {
            $url = $url->evaluate();
        }

        if (url_is_local($url)) {
            $url = get_custom_base_url() . '/' . $url;
        }
        $file_contents = http_download_file($url, 1024 * 1024 * 20/*reasonable limit*/);

        require_code('files');
        require_code('comcode_renderer');

        if ((array_key_exists('filename', $attributes)) && ($attributes['filename'] != '')) {
            $title = escape_html($attributes['filename']);
            $extension = get_file_extension($attributes['filename']);
        } else {
            $extension = get_file_extension(basename($url));
        }
        list($_embed, $title) = do_code_box($extension, make_string_tempcode($file_contents));

        return do_template('COMCODE_CODE', array(
            '_GUID' => 'b76f3383d31ad823f50124d59db6a8c3',
            'STYLE' => '',
            'TYPE' => $extension,
            'CONTENT' => $_embed,
            'TITLE' => $title,
        ));
    }
}
