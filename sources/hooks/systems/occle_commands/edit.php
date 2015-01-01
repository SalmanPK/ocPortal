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
 * @package    occle
 */

/**
 * Hook class.
 */
class Hook_occle_command_edit
{
    /**
     * Run function for OcCLE hooks.
     *
     * @param  array                    $options The options with which the command was called
     * @param  array                    $parameters The parameters with which the command was called
     * @param  object                   $occle_fs A reference to the OcCLE filesystem object
     * @return array                    Array of stdcommand, stdhtml, stdout, and stderr responses
     */
    public function run($options, $parameters, &$occle_fs)
    {
        if ((array_key_exists('h', $options)) || (array_key_exists('help', $options))) {
            return array('', do_command_help('edit', array('h'), array(true)), '', '');
        } else {
            // Show the editing UI
            if (!array_key_exists(0, $parameters)) {
                return array('', '', '', do_lang('MISSING_PARAM', '1', 'edit'));
            } else {
                $parameters[0] = $occle_fs->_pwd_to_array($parameters[0]);
            }

            if (!$occle_fs->_is_file($parameters[0])) {
                return array('', '', '', do_lang('NOT_A_FILE', '1'));
            }

            $file_contents = $occle_fs->read_file($parameters[0]);
            $parameters[0] = $occle_fs->pwd_to_string($parameters[0]);

            return array('', do_template('OCCLE_EDIT', array(
                '_GUID' => '8bbf2f9ef545a92b6865c35ed27cd6d4',
                'UNIQ_ID' => uniqid('', true),
                'FILE' => $parameters[0],
                'SUBMIT_URL' => build_url(array('page' => 'admin_occle', 'command' => 'write "' . $parameters[0] . '" "{0}" < :echo addslashes(get_param(\'edit_content\'));'), get_module_zone('admin_occle')),
                'FILE_CONTENTS' => $file_contents,
            )));
        }
    }
}
