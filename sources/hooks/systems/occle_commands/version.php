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
class Hook_occle_command_version
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
        require_code('version');
        require_code('version2');
        require_lang('version');

        if ((array_key_exists('h', $options)) || (array_key_exists('help', $options))) {
            return array('', do_command_help('version', array('h', 'f', 't', 'v', 'm'), array()), '', '');
        } elseif ((array_key_exists('f', $options)) || (array_key_exists('future', $options))) {
            return array('', get_future_version_information(), '', '');
        } elseif ((array_key_exists('t', $options)) || (array_key_exists('time', $options))) {
            return array('', '', ocp_version_time(), '');
        } elseif (((array_key_exists('v', $options)) || (array_key_exists('major-version', $options))) && ((!array_key_exists('m', $options)) && (!array_key_exists('minor-version', $options)))) {
            return array('', '', ocp_version(), '');
        } elseif (((array_key_exists('m', $options)) || (array_key_exists('minor-version', $options))) && ((!array_key_exists('v', $options)) && (!array_key_exists('major-version', $options)))) {
            return array('', '', ocp_version_minor(), '');
        } elseif (((array_key_exists('g', $options)) || (array_key_exists('general-version', $options))) && ((!array_key_exists('v', $options)) && (!array_key_exists('major-version', $options)))) {
            return array('', '', ocp_version_number(), '');
        } else {
            return array('', '', ocp_version_pretty(), '');
        }
    }
}
