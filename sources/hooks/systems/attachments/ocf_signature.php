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
 * @package    ocf_signatures
 */

class Hook_attachments_ocf_signature
{
    /**
     * Run function for attachment hooks. They see if permission to an attachment of an ID relating to this content is present for the current member.
     *
     * @param  ID_TEXT                  The ID
     * @param  object                   The database connection to check on
     * @return boolean                  Whether there is permission
     */
    public function run($id,$connection)
    {
        if (get_forum_type() != 'ocf') {
            return false;
        } // Shouldn't be here, but maybe it's left over somehow
        return true;
    }
}
