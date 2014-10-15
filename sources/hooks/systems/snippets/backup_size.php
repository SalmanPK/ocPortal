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
 * @package    backup
 */

class Hook_backup_size
{
    /**
     * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
     *
     * @return tempcode                 The snippet
     */
    public function run()
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        require_code('files');
        require_code('files2');

        $size = 0;
        $max_size = get_param_integer('max_size')*1024*1024;
        $files = get_directory_contents(get_custom_file_base());
        foreach ($files as $file) {
            $filesize = filesize(get_custom_file_base() . '/' . $file);
            if ($filesize<$max_size) {
                $size += $filesize;
            }
        }

        return make_string_tempcode(clean_file_size($size));
    }
}
