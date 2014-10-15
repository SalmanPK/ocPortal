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
 * @package    ssl
 */

/**
 * Module page class.
 */
class Module_admin_ssl
{
    /**
     * Find details of the module.
     *
     * @return ?array                   Map of module info (NULL: module is disabled).
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
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean                  Whether to check permissions.
     * @param  ?MEMBER                  The member to check permissions as (NULL: current user).
     * @param  boolean                  Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean                  Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array                   A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
     */
    public function get_entry_points($check_perms = true,$member_id = null,$support_crosslinks = true,$be_deferential = false)
    {
        return array(
            'misc' => array('SSL_CONFIGURATION','menu/adminzone/security/ssl'),
        );
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @return ?tempcode                Tempcode indicating some kind of exceptional output (NULL: none).
     */
    public function pre_run()
    {
        $type = get_param('type','misc');

        require_lang('ssl');

        set_helper_panel_tutorial('tut_security');

        $this->title = get_screen_title('SSL_CONFIGURATION');

        return NULL;
    }

    /**
     * Execute the module.
     *
     * @return tempcode                 The result of execution.
     */
    public function run()
    {
        if (!is_null($GLOBALS['CURRENT_SHARE_USER'])) {
            warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));
        }

        $type = get_param('type','misc');

        if ($type == 'set') {
            return $this->set();
        }
        if ($type == 'misc') {
            return $this->ssl_interface();
        }

        return new ocp_tempcode();
    }

    /**
     * The UI for selecting HTTPS pages.
     *
     * @return tempcode                 The UI
     */
    public function ssl_interface()
    {
        $content = new ocp_tempcode();
        $zones = find_all_zones();
        foreach ($zones as $zone) {
            $pages = find_all_pages_wrap($zone);
            $pk = array_keys($pages);
            @sort($pk); // @'d for inconsitency (some integer keys like 404) (annoying PHP quirk)
            foreach ($pk as $page) {
                if (!is_string($page)) {
                    $page = strval($page);
                } // strval($page) as $page could have become numeric due to array imprecision
                $ticked = is_page_https($zone,$page);
                $content->attach(do_template('SSL_CONFIGURATION_ENTRY',array('_GUID' => 'a08c339d93834f968c8936b099c677a3','TICKED' => $ticked,'PAGE' => $page,'ZONE' => $zone)));
            }
        }

        $url = build_url(array('page' => '_SELF','type' => 'set'),'_SELF');
        return do_template('SSL_CONFIGURATION_SCREEN',array('_GUID' => '823f395205f0c018861847e80c622710','URL' => $url,'TITLE' => $this->title,'CONTENT' => $content));
    }

    /**
     * The actualiser for selecting HTTPS pages.
     *
     * @return tempcode                 The UI
     */
    public function set()
    {
        $zones = find_all_zones();
        foreach ($zones as $zone) {
            $pages = find_all_pages_wrap($zone);
            foreach (array_keys($pages) as $page) {
                if (!is_string($page)) {
                    $page = strval($page);
                } // strval($page) as $page could have become numeric due to array imprecision
                $id = $zone . ':' . $page;
                $value = post_param_integer('ssl_' . $zone . '__' . $page,0);
                $GLOBALS['SITE_DB']->query_delete('https_pages',array('https_page_name' => $id),'',1);
                if ($value == 1) {
                    $GLOBALS['SITE_DB']->query_insert('https_pages',array('https_page_name' => $id));
                }
            }
        }

        erase_persistent_cache();

        // Show it worked / Refresh
        $url = build_url(array('page' => '_SELF','type' => 'misc'),'_SELF');
        return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
    }
}
