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

class Block_main_content_filtering
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
        $info['parameters'] = array('param','content_type','labels','types','links',);
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
        $links = array_key_exists('links',$map)?$map['links']:'';
        $labels = $this->interpret_pairs_from_string(array_key_exists('labels',$map)?$map['labels']:'');
        $_types = array_key_exists('types',$map)?$map['types']:'';
        $types = $this->interpret_pairs_from_string($_types);

        if ((strpos($_types,'linklist') !== false) || ($links != '')) { // Needs to be able to take overrides from environment if we have merge links
            $filter = either_param('active_filter',array_key_exists('param',$map)?$map['param']:'');
        } else {
            $filter = array_key_exists('param',$map)?$map['param']:'';
        }

        $content_type = mixed();
        if ((array_key_exists('content_type',$map)) && ($map['content_type'] != '')) {
            $content_type = $map['content_type'];

            if ((!file_exists(get_file_base() . '/sources/hooks/systems/content_meta_aware/' . filter_naughty_harsh($content_type) . '.php')) && (!file_exists(get_file_base() . '/sources_custom/hooks/systems/content_meta_aware/' . filter_naughty_harsh($content_type) . '.php'))) {
                return paragraph(do_lang_tempcode('NO_SUCH_CONTENT_TYPE',$content_type),'','red_alert');
            }
        }

        require_code('ocselect');

        list($fields,$filter,$_links) = form_for_ocselect($filter,$labels,$content_type,$types);

        // Filter links (different from form fields, works by overlaying)
        $_links2 = $this->interpret_pairs_from_string($links,'|');
        $_links = array_merge($_links,$_links2);
        $links = array();
        foreach ($_links as $link_title => $_link_filter) {
            $links[] = prepare_ocselect_merger_link($_link_filter)+array('TITLE' => $link_title);
        }

        return do_template('BLOCK_MAIN_CONTENT_FILTERING',array(
            '_GUID' => '6cdeed216dfac854672a16db39a6807f',
            'FIELDS' => $fields,
            'ACTIVE_FILTER' => $filter,
            'LINKS' => $links,
        ));
    }

    /**
     * Execute the module.
     *
     * @param  string                   Comma separated, equals separated, bits.
     * @param  string                   Separarator between pairs.
     * @return array                    Mapping.
     */
    public function interpret_pairs_from_string($str,$separator = ',')
    {
        $pairs = array();
        $matches = array();
        $num_matches = preg_match_all('#([^=]+)=(?U)(.+)(?-U)' . preg_quote($separator,'#') . '#',$str . $separator,$matches);
        for ($i = 0;$i<$num_matches;$i++) {
            $pairs[$matches[1][$i]] = $matches[2][$i];
        }
        return $pairs;
    }
}
