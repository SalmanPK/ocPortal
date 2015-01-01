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
 * @package    search
 */

/**
 * Generate a search block.
 *
 * @param  array                        $map Search block parameters
 * @return array                        Search block template parameters
 */
function do_search_block($map)
{
    require_lang('search');
    require_css('search');
    require_javascript('ajax_people_lists');

    $zone = array_key_exists('zone', $map) ? $map['zone'] : get_module_zone('search');

    $title = array_key_exists('title', $map) ? $map['title'] : null;
    if ($title === null) {
        $title = do_lang('SEARCH');
    }

    $sort = array_key_exists('sort', $map) ? $map['sort'] : 'relevance';
    $author = array_key_exists('author', $map) ? $map['author'] : '';
    $days = array_key_exists('days', $map) ? intval($map['days']) : -1;
    $direction = array_key_exists('direction', $map) ? $map['direction'] : 'DESC';
    $only_titles = (array_key_exists('only_titles', $map) ? $map['only_titles'] : '') == '1';
    $only_search_meta = (array_key_exists('only_search_meta', $map) ? $map['only_search_meta'] : '0') == '1';
    $boolean_search = (array_key_exists('boolean_search', $map) ? $map['boolean_search'] : '0') == '1';
    $conjunctive_operator = array_key_exists('conjunctive_operator', $map) ? $map['conjunctive_operator'] : 'AND';
    $_extra = array_key_exists('extra', $map) ? $map['extra'] : '';

    $map2 = array('page' => 'search', 'type' => 'results');
    if (array_key_exists('search_under', $map)) {
        $map2['search_under'] = $map['search_under'];
    }
    $url = build_url($map2, $zone, null, false, true);

    $extra = array();
    foreach (explode(',', $_extra) as $_bits) {
        $bits = explode('=', $_bits, 2);
        if (count($bits) == 2) {
            $extra[$bits[0]] = $bits[1];
        }
    }

    $input_fields = array('content' => do_lang('SEARCH_TITLE'));
    if (array_key_exists('input_fields', $map)) {
        $input_fields = array();
        foreach (explode(',', $map['input_fields']) as $_bits) {
            $bits = explode('=', $_bits, 2);
            if (count($bits) == 2) {
                $input_fields[$bits[0]] = $bits[1];
            }
        }
    }

    $limit_to = array('all_defaults');
    $extrax = array();
    if (array_key_exists('limit_to', $map)) {
        $limit_to = array();
        foreach (explode(',', $map['limit_to']) as $key) {
            $limit_to[] = 'search_' . $key;
            if (strpos($map['limit_to'], ',') !== false) {
                $extrax['search_' . $key] = '1';
            }
        }
        $hooks = find_all_hooks('modules', 'search');
        foreach (array_keys($hooks) as $key) {
            if (!array_key_exists('search_' . $key, $extrax)) {
                $extrax['search_' . $key] = '0';
            }
        }
        if (strpos($map['limit_to'], ',') === false) {
            $extra['id'] = $map['limit_to'];
        }
    }

    $url_map = $map;
    unset($url_map['input_fields']);
    unset($url_map['extra']);
    unset($url_map['zone']);
    unset($url_map['title']);
    unset($url_map['limit_to']);
    unset($url_map['block']);
    $full_link = build_url(array('page' => 'search', 'type' => 'browse') + $url_map + $extra + $extrax, $zone);

    if ((!array_key_exists('content', $input_fields)) && (count($input_fields) != 1)) {
        $extra['content'] = '';
    }

    return array(
        'TITLE' => $title,
        'INPUT_FIELDS' => $input_fields,
        'EXTRA' => $extra,
        'SORT' => $sort,
        'AUTHOR' => $author,
        'DAYS' => strval($days),
        'DIRECTION' => $direction,
        'ONLY_TITLES' => $only_titles ? '1' : '0',
        'ONLY_SEARCH_META' => $only_search_meta ? '1' : '0',
        'BOOLEAN_SEARCH' => $boolean_search ? '1' : '0',
        'CONJUNCTIVE_OPERATOR' => $conjunctive_operator,
        'LIMIT_TO' => $limit_to,
        'URL' => $url,
        'FULL_SEARCH_URL' => $full_link,
    );
}
