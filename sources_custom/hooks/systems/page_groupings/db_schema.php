<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    db_schema
 */

class Hook_page_groupings_db_schema
{
    /**
     * Run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
     *
     * @param  ?MEMBER                  Member ID to run as (NULL: current member)
     * @param  boolean                  Whether to use extensive documentation tooltips, rather than short summaries
     * @return array                    List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
     */
    public function run($member_id = null,$extensive_docs = false)
    {
        return array(
            array('tools','menu/_generic_admin/tool',array('sql_schema_generate',array(),get_page_zone('sql_schema_generate')),make_string_tempcode('Doc build: Generate database schema')),
            array('tools','menu/_generic_admin/tool',array('sql_schema_generate_by_addon',array(),get_page_zone('sql_schema_generate_by_addon')),make_string_tempcode('Doc build: Generate database schema, by addon')),
            array('tools','menu/_generic_admin/tool',array('sql_show_tables_by_addon',array(),get_page_zone('sql_show_tables_by_addon')),make_string_tempcode('Doc build: Show database tables, by addon')),
        );
    }
}
