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
 * @package    catalogues
 */

class Hook_symbol_CATALOGUE_ENTRY_BACKREFS
{
    /**
     * Run function for symbol hooks. Searches for tasks to perform.
    *
    * @param  array                     Symbol parameters
    * @return string                    Result
     */
    public function run($param)
    {
        $value = '';
        if (isset($param[0])) {
            $limit = isset($param[1])?intval($param[1]):null;
            $resolve = isset($param[2])?$param[2]:''; // Content-type to associate back to, and fetch the ID for
            $rating_type = isset($param[3])?$param[3]:''; // If non empty, it will get the highest rated first

            static $cache = array();
            $cache_key = serialize($param);
            if (isset($cache[$cache_key])) {
                return $cache[$cache_key];
            }

            $done = 0;
            $table = 'catalogue_fields f JOIN ' . get_table_prefix() . 'catalogue_efv_short s ON f.id=s.cf_id AND ' . db_string_equal_to('cf_type','reference') . ' OR cf_type LIKE \'' . db_encode_like('ck_%') . '\'';
            $select = array('ce_id');
            $order_by = '';
            if ($resolve != '') {
                $table .= ' JOIN ' . get_table_prefix() . 'catalogue_entry_linkage ON ' . db_string_equal_to('content_type',$param[2]) . ' AND catalogue_entry_id=ce_id';
                $select[] = 'content_id';

                if ($rating_type != '') {
                    $select[] = '(SELECT AVG(rating) FROM ' . get_table_prefix() . 'rating WHERE ' . db_string_equal_to('rating_for_type',$rating_type) . ' AND rating_for_id=content_id) AS average_rating';
                    $order_by = 'ORDER BY average_rating DESC';
                }
            }
            $results = $GLOBALS['SITE_DB']->query_select($table,$select,array('cv_value' => $param[0]),$order_by);
            foreach ($results as $result) {
                if ($value != '') {
                    $value .= ',';
                }
                if ($resolve != '') {
                    $value .= $result['content_id'];
                } else {
                    $value .= strval($result['ce_id']);
                }
                $done++;

                if (($limit !== NULL) && ($done == $limit)) {
                    break;
                }
            }

            $cache[$cache_key] = $value;
        }
        return $value;
    }
}
