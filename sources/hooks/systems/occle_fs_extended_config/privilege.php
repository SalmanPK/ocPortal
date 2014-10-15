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
 * @package    core
 */

class Hook_occle_fs_extended_config__privilege
{
    /**
     * Standard occle_fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @return ?TIME                    The edit date or add date, whichever is higher (NULL: could not find one)
     */
    public function _get_edit_date()
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'adminlogs WHERE ' . db_string_equal_to('the_type','PAGE_ACCESS') . ' OR ' . db_string_equal_to('the_type','PRIVILEGES');
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard occle_fs file reading function for OcCLE FS hooks.
     *
     * @param  array                    The current meta-directory path
     * @param  string                   The root node of the current meta-directory
     * @param  string                   The file name
     * @param  object                   A reference to the OcCLE filesystem object
     * @return ~string                  The file contents (false: failure)
     */
    public function read_file($meta_dir,$meta_root_node,$file_name,&$occle_fs)
    {
        require_code('resource_fs');

        $tables = array(
            'group_privileges' => array('category_name' => ''),
            'member_privileges' => array('category_name' => ''),
            'group_page_access' => array(),
            'member_page_access' => array(),
        );
        $all = array();
        foreach ($tables as $table => $map) {
            $rows = $GLOBALS['SITE_DB']->query_select($table,array('*'),$map);
            foreach ($rows as $i => $row) {
                if (array_key_exists('member_id',$row)) {
                    $rows[$i]['member_id'] = remap_resource_id_as_portable('member',strval($row['member_id']));
                }
                if (array_key_exists('group_id',$row)) {
                    $rows[$i]['group_id'] = remap_resource_id_as_portable('group',strval($row['group_id']));
                }
            }
            $all[$table] = $rows;
        }
        return serialize($all);
    }

    /**
     * Standard occle_fs file writing function for OcCLE FS hooks.
     *
     * @param  array                    The current meta-directory path
     * @param  string                   The root node of the current meta-directory
     * @param  string                   The file name
     * @param  string                   The new file contents
     * @param  object                   A reference to the OcCLE filesystem object
     * @return boolean                  Success?
     */
    public function write_file($meta_dir,$meta_root_node,$file_name,$contents,&$occle_fs)
    {
        $all = @unserialize($contents);
        if ($all === false) {
            return false;
        }

        foreach ($all as $table => $rows) {
            $GLOBALS['SITE_DB']->query_delete($table);
            foreach ($rows as $row) {
                if (array_key_exists('member_id',$row)) {
                    $row['member_id'] = remap_portable_as_resource_id('member',$row['member_id']);
                }
                if (array_key_exists('group_id',$row)) {
                    $row['group_id'] = remap_portable_as_resource_id('group',$row['group_id']);
                }

                $GLOBALS['SITE_DB']->query_insert($table,$row);
            }
        }
        return true;
    }
}
