<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    iotds
 */

class Hook_sitemap_iotd extends Hook_sitemap_content
{
    protected $content_type = 'iotd';
    protected $screen_type = 'view';

    // If we have a different content type of entries, under this content type
    protected $entry_content_type = null;
    protected $entry_sitetree_hook = null;

    /**
     * Get the permission page that nodes matching $page_link in this hook are tied to.
     * The permission page is where privileges may be overridden against.
     *
     * @param  string                   The page-link
     * @return ?ID_TEXT                 The permission page (NULL: none)
     */
    public function get_privilege_page($page_link)
    {
        return 'cms_iotds';
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

        $start = 0;
        do {
            $rows = $GLOBALS['SITE_DB']->query_select('iotd',array('*'),$consider_validation?array('used' => 1):array(),'ORDER BY date_and_time',SITEMAP_MAX_ROWS_PER_LOOP,$start);
            foreach ($rows as $row) {
                $child_page_link = $zone . ':' . $page . ':' . $this->screen_type . ':' . strval($row['id']);
                $node = $this->get_node($child_page_link,$callback,$valid_node_types,$child_cutoff,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,$row);
                if (($callback === NULL || $return_anyway) && ($node !== NULL)) {
                    $nodes[] = $node;
                }
            }

            $start += SITEMAP_MAX_ROWS_PER_LOOP;
        } while (count($rows)>0);

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
        $_ = $this->_create_partial_node_structure($page_link,$callback,$valid_node_types,$child_cutoff,$max_recurse_depth,$recurse_level,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,$row);
        if ($_ === NULL) {
            return NULL;
        }
        list($content_id,$row,$partial_struct) = $_;

        $struct = array(
            'sitemap_priority' => SITEMAP_IMPORTANCE_LOW,
            'sitemap_refreshfreq' => 'never',

            'privilege_page' => $this->get_privilege_page($page_link),
        )+$partial_struct;

        if (!$this->_check_node_permissions($struct)) {
            return NULL;
        }

        if ($callback !== NULL) {
            call_user_func($callback,$struct);
        }

        return ($callback === NULL || $return_anyway)?$struct:null;
    }
}
