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

class Hook_sitemap_catalogue extends Hook_sitemap_content
{
    protected $content_type = 'catalogue';
    protected $screen_type = 'index';

    // If we have a different content type of entries, under this content type
    protected $entry_content_type = array('catalogue_category');
    protected $entry_sitetree_hook = array('catalogue_category');

    /**
     * Find if a page-link will be covered by this node.
     *
     * @param  ID_TEXT                  The page-link.
     * @return integer                  A SITEMAP_NODE_* constant.
     */
    public function handles_page_link($page_link)
    {
        $matches = array();
        if (preg_match('#^([^:]*):([^:]*)#',$page_link,$matches) != 0) {
            $zone = $matches[1];
            $page = $matches[2];

            require_code('content');
            $cma_ob = get_content_object($this->content_type);
            $cma_info = $cma_ob->info();
            require_code('site');
            if (($cma_info['module'] == $page) && ($zone != '_SEARCH') && (_request_page($page,$zone) !== false)) { // Ensure the given page matches the content type, and it really does exist in the given zone
                if ($matches[0] == $page_link) {
                    return SITEMAP_NODE_HANDLED_VIRTUALLY;
                } // No type/ID specified
                if (preg_match('#^([^:]*):([^:]*):(index|atoz)(:|$)#',$page_link,$matches) != 0) {
                    return SITEMAP_NODE_HANDLED;
                }
            }
        }
        return SITEMAP_NODE_NOT_HANDLED;
    }

    /**
     * Get the permission page that nodes matching $page_link in this hook are tied to.
     * The permission page is where privileges may be overridden against.
     *
     * @param  string                   The page-link
     * @return ?ID_TEXT                 The permission page (NULL: none)
     */
    public function get_privilege_page($page_link)
    {
        return 'cms_catalogues';
    }

    /**
     * Find details of a virtual position in the sitemap. Virtual positions have no structure of their own, but can find child structures to be absorbed down the tree. We do this for modularity reasons.
     *
     * @param  ID_TEXT                  The page-link we are finding.
     * @param  ?string                  Callback function to send discovered page-links to (NULL: return).
     * @param  ?array                   List of node types we will return/recurse-through (NULL: no limit)
     * @param  ?integer                 Maximum number of children before we cut off all children (NULL: no limit).
     * @param  ?integer                 How deep to go from the sitemap root (NULL: no limit).
     * @param  integer                  Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by Google sitemap [deeper is typically less important]).
     * @param  boolean                  Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
     * @param  ID_TEXT                  The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
     * @param  boolean                  Whether to make use of page groupings, to organise stuff with the hook schema, supplementing the default zone organisation.
     * @param  boolean                  Whether to consider secondary categorisations for content that primarily exists elsewhere.
     * @param  boolean                  Whether to filter out non-validated content.
     * @param  integer                  A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
     * @param  boolean                  Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
     * @return ?array                   List of node structures (NULL: working via callback).
     */
    public function get_virtual_nodes($page_link,$callback = null,$valid_node_types = null,$child_cutoff = null,$max_recurse_depth = null,$recurse_level = 0,$require_permission_support = false,$zone = '_SEARCH',$use_page_groupings = false,$consider_secondary_categories = false,$consider_validation = false,$meta_gather = 0,$return_anyway = false)
    {
        $nodes = ($callback === NULL || $return_anyway)?array():mixed();

        if (($valid_node_types !== NULL) && (!in_array($this->content_type,$valid_node_types))) {
            return $nodes;
        }

        if ($require_permission_support) {
            return $nodes;
        }

        $page = $this->_make_zone_concrete($zone,$page_link);

        if ($child_cutoff !== NULL) {
            $count = $GLOBALS['SITE_DB']->query_select_value('catalogues','COUNT(*)');
            if ($count>$child_cutoff) {
                return $nodes;
            }
        }

        $start = 0;
        do {
            $rows = $GLOBALS['SITE_DB']->query_select('catalogues',array('*'),null,'',SITEMAP_MAX_ROWS_PER_LOOP,$start);
            foreach ($rows as $row) {
                if (substr($row['c_name'],0,1) != '_') {
                    // Index
                    $child_page_link = $zone . ':' . $page . ':index:' . $row['c_name'];
                    $node = $this->get_node($child_page_link,$callback,$valid_node_types,$child_cutoff,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,$row);
                    if (($callback === NULL || $return_anyway) && ($node !== NULL)) {
                        $nodes[] = $node;
                    }
                }
            }

            $start += SITEMAP_MAX_ROWS_PER_LOOP;
        } while (count($rows) == SITEMAP_MAX_ROWS_PER_LOOP);

        if (is_array($nodes)) {
            sort_maps_by($nodes,'title');
        }

        return $nodes;
    }

    /**
     * Find details of a position in the Sitemap.
     *
     * @param  ID_TEXT                  The page-link we are finding.
     * @param  ?string                  Callback function to send discovered page-links to (NULL: return).
     * @param  ?array                   List of node types we will return/recurse-through (NULL: no limit)
     * @param  ?integer                 Maximum number of children before we cut off all children (NULL: no limit).
     * @param  ?integer                 How deep to go from the Sitemap root (NULL: no limit).
     * @param  integer                  Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by XML Sitemap [deeper is typically less important]).
     * @param  boolean                  Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
     * @param  ID_TEXT                  The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
     * @param  boolean                  Whether to make use of page groupings, to organise stuff with the hook schema, supplementing the default zone organisation.
     * @param  boolean                  Whether to consider secondary categorisations for content that primarily exists elsewhere.
     * @param  boolean                  Whether to filter out non-validated content.
     * @param  integer                  A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
     * @param  ?array                   Database row (NULL: lookup).
     * @param  boolean                  Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
     * @return ?array                   Node structure (NULL: working via callback / error).
     */
    public function get_node($page_link,$callback = null,$valid_node_types = null,$child_cutoff = null,$max_recurse_depth = null,$recurse_level = 0,$require_permission_support = false,$zone = '_SEARCH',$use_page_groupings = false,$consider_secondary_categories = false,$consider_validation = false,$meta_gather = 0,$row = null,$return_anyway = false)
    {
        $page_link_fudged = preg_replace('#:catalogue_name=#',':',$page_link);
        $_ = $this->_create_partial_node_structure($page_link_fudged,$callback,$valid_node_types,$child_cutoff,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,$row);
        if ($_ === NULL) {
            return NULL;
        }
        list($content_id,$row,$partial_struct) = $_;

        $matches = array();
        preg_match('#^([^:]*):([^:]*)#',$page_link,$matches);
        $page = $matches[2];

        $this->_make_zone_concrete($zone,$page_link);

        $struct = array(
            'sitemap_priority' => SITEMAP_IMPORTANCE_MEDIUM,
            'sitemap_refreshfreq' => 'weekly',

            'privilege_page' => $this->get_privilege_page($page_link),
        )+$partial_struct;

        if (strpos($page_link,':index:') !== false) {
            $struct['extra_meta']['description'] = null;

            if (($meta_gather & SITEMAP_GATHER_IMAGE) != 0) {
                $test = find_theme_image('icons/24x24/menu/rich_content/catalogues/' . $content_id,true);
                if ($test != '') {
                    $struct['extra_meta']['image'] = $test;
                }
                $test = find_theme_image('icons/48x48/menu/rich_content/catalogues/' . $content_id,true);
                if ($test != '') {
                    $struct['extra_meta']['image_2x'] = $test;
                }
            }

            if (($max_recurse_depth === NULL) || ($recurse_level<$max_recurse_depth)) {
                $children = array();

                // A-to-Z
                $child_page_link = $zone . ':' . $page . ':atoz:catalogue_name=' . $content_id;
                $child_node = $this->get_node($child_page_link,$callback,$valid_node_types,$child_cutoff,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,$row);
                if ($child_node !== NULL) {
                    $children[] = $child_node;
                }

                // Categories
                $count = $GLOBALS['SITE_DB']->query_select_value_if_there('catalogue_categories','COUNT(*)',array('c_name' => $content_id));
                $lots = ($count>3000) || ($child_cutoff !== NULL) && ($count>$child_cutoff);
                if (!$lots) {
                    $child_hook_ob = $this->_get_sitemap_object('catalogue_category');

                    $children_entries = array();
                    $start = 0;
                    do {
                        $where = array('c_name' => $content_id,'cc_parent_id' => NULL);
                        $rows = $GLOBALS['SITE_DB']->query_select('catalogue_categories',array('*'),$where,'',SITEMAP_MAX_ROWS_PER_LOOP,$start);
                        foreach ($rows as $child_row) {
                            $child_page_link = $zone . ':' . $page . ':misc:' . strval($child_row['id']);
                            $child_node = $child_hook_ob->get_node($child_page_link,$callback,$valid_node_types,$child_cutoff,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,$child_row);
                            if ($child_node !== NULL) {
                                if (($meta_gather & SITEMAP_GATHER_IMAGE) != 0) {
                                    $test = find_theme_image('icons/24x24/menu/_generic_admin/view_this_category',true);
                                    if ($test != '') {
                                        $child_node['extra_meta']['image'] = $test;
                                    }
                                    $test = find_theme_image('icons/48x48/menu/_generic_admin/view_this_category',true);
                                    if ($test != '') {
                                        $child_node['extra_meta']['image_2x'] = $test;
                                    }
                                }

                                $children_entries[] = $child_node;
                            }
                        }
                        $start += SITEMAP_MAX_ROWS_PER_LOOP;
                    } while (count($rows) == SITEMAP_MAX_ROWS_PER_LOOP);

                    sort_maps_by($children_entries,'title');

                    $children = array_merge($children,$children_entries);
                }

                $struct['children'] = $children;
            }
        } elseif (strpos($page_link,':atoz:') !== false) {
            $struct['page_link'] = $page_link;

            $struct['extra_meta']['description'] = null;

            $struct['title'] = do_lang_tempcode('catalogues:ATOZ');

            if (($meta_gather & SITEMAP_GATHER_IMAGE) != 0) {
                $test = find_theme_image('icons/24x24/menu/rich_content/atoz',true);
                if ($test != '') {
                    $struct['extra_meta']['image'] = $test;
                }
                $test = find_theme_image('icons/48x48/menu/rich_content/atoz',true);
                if ($test != '') {
                    $struct['extra_meta']['image_2x'] = $test;
                }
            }
        } else {
            warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }

        if (!$this->_check_node_permissions($struct)) {
            return NULL;
        }

        if ($callback !== NULL) {
            call_user_func($callback,$struct);
        }

        return ($callback === NULL || $return_anyway)?$struct:null;
    }
}
