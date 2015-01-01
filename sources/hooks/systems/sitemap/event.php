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
 * @package    calendar
 */

/**
 * Hook class.
 */
class Hook_sitemap_event extends Hook_sitemap_content
{
    protected $content_type = 'event';
    protected $screen_type = 'view';

    // If we have a different content type of entries, under this content type
    protected $entry_content_type = null;
    protected $entry_sitetree_hook = null;

    /**
     * Get the permission page that nodes matching $page_link in this hook are tied to.
     * The permission page is where privileges may be overridden against.
     *
     * @param  string                   $page_link The page-link
     * @return ?ID_TEXT                 The permission page (null: none)
     */
    public function get_privilege_page($page_link)
    {
        return 'cms_calendar';
    }

    /**
     * Find details of a position in the Sitemap.
     *
     * @param  ID_TEXT                  $page_link The page-link we are finding.
     * @param  ?string                  $callback Callback function to send discovered page-links to (null: return).
     * @param  ?array                   $valid_node_types List of node types we will return/recurse-through (null: no limit)
     * @param  ?integer                 $child_cutoff Maximum number of children before we cut off all children (null: no limit).
     * @param  ?integer                 $max_recurse_depth How deep to go from the Sitemap root (null: no limit).
     * @param  integer                  $recurse_level Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by XML Sitemap [deeper is typically less important]).
     * @param  boolean                  $require_permission_support Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
     * @param  ID_TEXT                  $zone The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
     * @param  boolean                  $use_page_groupings Whether to make use of page groupings, to organise stuff with the hook schema, supplementing the default zone organisation.
     * @param  boolean                  $consider_secondary_categories Whether to consider secondary categorisations for content that primarily exists elsewhere.
     * @param  boolean                  $consider_validation Whether to filter out non-validated content.
     * @param  integer                  $meta_gather A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
     * @param  ?array                   $row Database row (null: lookup).
     * @param  boolean                  $return_anyway Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
     * @return ?array                   Node structure (null: working via callback / error).
     */
    public function get_node($page_link, $callback = null, $valid_node_types = null, $child_cutoff = null, $max_recurse_depth = null, $recurse_level = 0, $require_permission_support = false, $zone = '_SEARCH', $use_page_groupings = false, $consider_secondary_categories = false, $consider_validation = false, $meta_gather = 0, $row = null, $return_anyway = false)
    {
        $_ = $this->_create_partial_node_structure($page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather, $row);
        if ($_ === null) {
            return null;
        }
        list($content_id, $row, $partial_struct) = $_;

        $struct = array(
                'sitemap_priority' => SITEMAP_IMPORTANCE_HIGH,
                'sitemap_refreshfreq' => 'weekly',

                'privilege_page' => $this->get_privilege_page($page_link),
            ) + $partial_struct;

        if (!$this->_check_node_permissions($struct)) {
            return null;
        }

        if ($callback !== null) {
            call_user_func($callback, $struct);
        }

        return ($callback === null || $return_anyway) ? $struct : null;
    }
}
