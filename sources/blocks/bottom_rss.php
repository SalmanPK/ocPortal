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
 * @package    syndication_blocks
 */

class Block_bottom_rss
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
        $info['parameters'] = array('param','max_entries');
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
        $info['cache_on'] = array('block_bottom_rss__cache_on');
        $info['ttl'] = intval(get_option('rss_update_time'));
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
        $url = array_key_exists('param',$map)?$map['param']:(get_brand_base_url() . '/backend.php?type=rss&mode=news&filter=16,17,18,19,20'); // http://channel9.msdn.com/Feeds/RSS/

        require_code('rss');
        $rss = new rss($url);
        if (!is_null($rss->error)) {
            return do_template('WARNING_BOX',array('_GUID' => '7ae6a91db7c7ac7d607b9e29ddafc344','WARNING' => $rss->error));
        }

        global $NEWS_CATS_CACHE;
        $NEWS_CATS_CACHE = $GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('nc_owner' => NULL));
        $NEWS_CATS_CACHE = list_to_map('id',$NEWS_CATS_CACHE);

        $_postdetailss = array();

        // Now for the actual stream contents
        $max = array_key_exists('max_entries',$map)?intval($map['max_entries']):10;
        $content = new ocp_tempcode();
        foreach ($rss->gleamed_items as $i => $item) {
            if ($i >= $max) {
                break;
            }

            if (array_key_exists('full_url',$item)) {
                $full_url = $item['full_url'];
            } elseif (array_key_exists('guid',$item)) {
                $full_url = $item['guid'];
            } elseif (array_key_exists('comment_url',$item)) {
                $full_url = $item['comment_url'];
            } else {
                $full_url = '';
            }

            $_title = $item['title'];
            $date = array_key_exists('clean_add_date',$item)?get_timezoned_date($item['clean_add_date']):array_key_exists('add_date',$item)?$item['add_date']:'';

            $_postdetailss[] = array('DATE' => $date,'FULL_URL' => $full_url,'NEWS_TITLE' => $_title);
        }

        return do_template('BLOCK_BOTTOM_NEWS',array('_GUID' => '0fc123199c4d4b7af5a26706271b1f4f','POSTS' => $_postdetailss));
    }
}

/**
 * Find the cache signature for the block.
 *
 * @param  array                        The block parameters.
 * @return array                        The cache signature.
 */
function block_bottom_rss__cache_on($map)
{
    return array(array_key_exists('param',$map)?$map['param']:'',array_key_exists('max_entries',$map)?intval($map['max_entries']):10);
}
