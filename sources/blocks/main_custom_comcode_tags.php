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
 * @package    custom_comcode
 */

/**
 * Block class.
 */
class Block_main_custom_comcode_tags
{
    /**
     * Find details of the block.
     *
     * @return ?array                   Map of block info (null: block is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array();
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array                    $map A map of parameters.
     * @return tempcode                 The result of execution.
     */
    public function run($map)
    {
        require_lang('custom_comcode');

        $tags = array();
        $wmap = array('tag_enabled' => 1);
        if (!has_privilege(get_member(), 'comcode_dangerous')) {
            $wmap['tag_dangerous_tag'] = 0;
        }
        $tags = array_merge($tags, $GLOBALS['SITE_DB']->query_select('custom_comcode', array('tag_title', 'tag_description', 'tag_example', 'tag_parameters', 'tag_replace', 'tag_tag', 'tag_dangerous_tag', 'tag_block_tag', 'tag_textual_tag'), $wmap));
        if ((isset($GLOBALS['FORUM_DB'])) && ($GLOBALS['FORUM_DB']->connection_write != $GLOBALS['SITE_DB']->connection_write) && (get_forum_type() == 'ocf')) {
            $tags = array_merge($tags, $GLOBALS['FORUM_DB']->query_select('custom_comcode', array('tag_title', 'tag_description', 'tag_example', 'tag_parameters', 'tag_replace', 'tag_tag', 'tag_dangerous_tag', 'tag_block_tag', 'tag_textual_tag'), $wmap));
        }

        // From Comcode hooks
        $hooks = find_all_hooks('systems', 'comcode');
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/comcode/' . filter_naughty_harsh($hook));
            $object = object_factory('Hook_comcode_' . filter_naughty_harsh($hook), true);

            $tags[] = $object->get_tag();
        }

        if (!array_key_exists(0, $tags)) {
            return paragraph(do_lang_tempcode('NONE_EM'), '', 'nothing_here');
        }

        $content = new Tempcode();
        foreach ($tags as $tag) {
            $content->attach(do_template('CUSTOM_COMCODE_TAG_ROW', array(
                '_GUID' => '28c257f5d0c596aa828fd9556b0df4a9',
                'TITLE' => is_string($tag['tag_title']) ? $tag['tag_title'] : get_translated_text($tag['tag_title']),
                'DESCRIPTION' => is_string($tag['tag_description']) ? $tag['tag_description'] : get_translated_text($tag['tag_description']),
                'EXAMPLE' => $tag['tag_example'],
            )));
        }

        return do_template('BLOCK_MAIN_CUSTOM_COMCODE_TAGS', array('_GUID' => 'b8d3436e6e5fe679ae9b0a368e607610', 'TAGS' => $content));
    }
}
