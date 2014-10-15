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
 * @package    galleries
 */

class Block_side_galleries
{
    /**
     * Find details of the block.
     *
     * @return ?array                   Map of block info (NULL: block is disabled).
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
        $info['parameters'] = array('param','depth','zone','show_empty');
        return $info;
    }

    /**
     * Find cacheing details for the block.
     *
     * @return ?array                   Map of cache details (cache_on and ttl) (NULL: block is disabled).
     */
    public function cacheing_environment()
    {
        $info = array();
        $info['cache_on'] = 'array(has_actual_page_access(get_member(),\'galleries\',array_key_exists(\'zone\',$map)?$map[\'zone\']:get_module_zone(\'galleries\')),array_key_exists(\'depth\',$map)?intval($map[\'depth\']):0,array_key_exists(\'param\',$map)?$map[\'param\']:\'root\',array_key_exists(\'zone\',$map)?$map[\'zone\']:\'\',array_key_exists(\'show_empty\',$map)?($map[\'show_empty\']==\'1\'):false)';
        $info['ttl'] = (get_value('no_block_timeout') === '1')?60*60*24*365*5/*5 year timeout*/:60*2;
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array                    A map of parameters.
     * @return tempcode                 The result of execution.
     */
    public function run($map)
    {
        require_lang('galleries');
        require_code('galleries');
        require_css('galleries');

        $parent_id = array_key_exists('param',$map)?$map['param']:'root';

        $zone = array_key_exists('zone',$map)?$map['zone']:get_module_zone('galleries');

        $show_empty = array_key_exists('show_empty',$map)?($map['show_empty'] == '1'):false;

        $depth = array_key_exists('depth',$map)?intval($map['depth']):0; // If depth is 1 then we go down 1 level. Only 0 or 1 is supported.

        // For all galleries off the root gallery
        $query = 'SELECT name,fullname FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'galleries WHERE ' . db_string_equal_to('parent_id',$parent_id) . ' AND name NOT LIKE \'' . db_encode_like('download\_%') . '\' ORDER BY add_date';
        $galleries = $GLOBALS['SITE_DB']->query($query,300 /*reasonable limit*/);
        if ($depth == 0) {
            $content = $this->inside($zone,$galleries,'BLOCK_SIDE_GALLERIES_LINE',$show_empty);
        } else {
            $content = new ocp_tempcode();

            foreach ($galleries as $gallery) {
                if (($show_empty) || (gallery_has_content($gallery['name']))) {
                    $subgalleries = $GLOBALS['SITE_DB']->query_select('galleries',array('name','fullname'),array('parent_id' => $gallery['name']),'ORDER BY add_date',300 /*reasonable limit*/);
                    $nest = $this->inside($zone,$subgalleries,'BLOCK_SIDE_GALLERIES_LINE_DEPTH',$show_empty);
                    $caption = get_translated_text($gallery['fullname']);
                    $content->attach(do_template('BLOCK_SIDE_GALLERIES_LINE_CONTAINER',array('_GUID' => 'e50b84369b5e2146c4fab4fddc84bf0a','ID' => $gallery['name'],'CAPTION' => $caption,'CONTENTS' => $nest)));
                }
            }
        }

        $_title = $GLOBALS['SITE_DB']->query_select_value_if_there('galleries','fullname',array('name' => $parent_id));
        if (!is_null($_title)) {
            $title = get_translated_text($_title);
        } else {
            $title = '';
        }

        return do_template('BLOCK_SIDE_GALLERIES',array('_GUID' => 'ed420ce9d1b1dde95eb3fd8473090228','TITLE' => $title,'ID' => $parent_id,'DEPTH' => $depth != 0,'CONTENT' => $content));
    }

    /**
     * Show a group of subgalleries for use in a compact tree structure.
     *
     * @param  ID_TEXT                  The zone our gallery module is in
     * @param  array                    A list of gallery rows
     * @param  ID_TEXT                  The template to use to show each subgallery
     * @param  boolean                  Whether to show empty galleries
     * @return tempcode                 The shown galleries
     */
    public function inside($zone,$galleries,$tpl,$show_empty)
    {
        $content = new ocp_tempcode();

        foreach ($galleries as $gallery) {
            if (($show_empty) || (gallery_has_content($gallery['name']))) {
                $url = build_url(array('page' => 'galleries','type' => 'misc','id' => $gallery['name']),$zone);
                $content->attach(do_template($tpl,array('TITLE' => get_translated_text($gallery['fullname']),'URL' => $url)));
            }
        }

        return $content;
    }
}
