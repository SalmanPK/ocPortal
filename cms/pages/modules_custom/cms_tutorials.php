<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    ocportal_tutorials
 */

require_code('crud_module');

/**
 * Module page class.
 */
class Module_cms_tutorials extends Standard_crud_module
{
    public $lang_type = 'TUTORIAL';
    public $special_edit_frontend = true;
    public $archive_entry_point = '_SEARCH:tutorials';
    public $user_facing = true;
    public $send_validation_request = true;
    public $permissions_require = 'low';
    public $menu_label = 'TUTORIALS';
    public $select_name = 'TUTORIALS';
    public $orderer = 't_title';
    public $table = 'tutorials_external';

    /**
     * Standard crud_module run_start.
     *
     * @param  ID_TEXT                  $type The type of module execution
     * @return tempcode                 The output of the run
     */
    public function run_start($type)
    {
        i_solemnly_declare(I_UNDERSTAND_SQL_INJECTION | I_UNDERSTAND_XSS | I_UNDERSTAND_PATH_INJECTION);

        require_code('tutorials');
        require_lang('tutorials');

        return new Tempcode();
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean                  $check_perms Whether to check permissions.
     * @param  ?MEMBER                  $member_id The member to check permissions as (null: current user).
     * @param  boolean                  $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean                  $be_deferential Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array                   A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        return array(
            'browse' => array('tutorials:TUTORIALS', 'menu/pages/help'),
        ) + parent::get_entry_points();
    }

    /**
     * Get tempcode for an external tutorial adding/editing form.
     *
     * @param  ?AUTO_LINK               $id ID (NULL: not added yet)
     * @param  URLPATH                  $url URL
     * @param  SHORT_TEXT               $title Title
     * @param  LONG_TEXT                $summary Summary
     * @param  URLPATH                  $icon Icon
     * @param  ID_TEXT                  $media_type Media type
     * @set document video audio slideshow
     * @param  ID_TEXT                  $difficulty_level Difficulty level
     * @set novice regular expert
     * @param  BINARY                   $pinned Whether is pinned
     * @param  ID_TEXT                  $author Author
     * @param  array                    $tags List of tags
     * @return array                    A pair: the tempcode for the visible fields, and the tempcode for the hidden fields
     */
    public function get_form_fields($id = null, $url = '', $title = '', $summary = '', $icon = '', $media_type = 'document', $difficulty_level = 'regular', $pinned = 0, $author = '', $tags = null)
    {
        if ($author == '') {
            $author = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
            if ($GLOBALS['FORUM_DRIVER']->is_staff(get_member())) {
                $author .= ', ocProducts';
            }
        }
        if ($tags === null) {
            $tags = array();
        }

        $fields = new Tempcode();

        $hidden = new Tempcode();

        $fields->attach(form_input_url('URL', 'The direct URL to the tutorial (be it on a private website, on YouTube, etc).', 'url', $url, true));

        $fields->attach(form_input_line('Title', 'The title to the tutorial.', 'title', $title, true));

        $fields->attach(form_input_text('Summary', 'A short paragraph describing the tutorial.', 'summary', $summary, true));

        require_code('themes2');
        $ids = get_all_image_ids_type('tutorial_icons');
        $fields->attach(form_input_theme_image('Icon', 'Icon for the tutorial.', 'icon', $ids, $icon));

        $content = new Tempcode();
        foreach (array('document', 'video', 'audio', 'slideshow') as $_media_type) {
            $content->attach(form_input_list_entry($_media_type, $_media_type == $media_type, titleify($_media_type)));
        }
        $fields->attach(form_input_list('Media type', 'What kind of media is the tutorial presented in.', 'media_type', $content));

        $content = new Tempcode();
        foreach (array('novice', 'regular', 'expert') as $_difficulty_level) {
            $content->attach(form_input_list_entry($_difficulty_level, $_difficulty_level == $difficulty_level, titleify($_difficulty_level)));
        }
        $fields->attach(form_input_list('Difficulty level', 'A realistic assessment of who should be reading this. If it has different sections for different levels, choose the lowest, but make sure the tutorial somehow specifies which are the hard bits.', 'difficulty_level', $content));

        if ($GLOBALS['FORUM_DRIVER']->is_staff(get_member())) {
            $fields->attach(form_input_tick('Pinned', 'Whether the tutorial is pinned (curated onto front page).', 'pinned', $pinned == 1));
        }

        $fields->attach(form_input_author('Author', 'Who wrote this tutorial.', 'author', $author, true));

        $content = new Tempcode();
        foreach (list_tutorial_tags() as $_tag) {
            $content->attach(form_input_list_entry($_tag, in_array($_tag, $tags), $_tag));
        }
        $fields->attach(form_input_multi_list('Tags', 'At least one tag to classify the tutorial. Use as many as are appropriate.', 'tags', $content, null, 5, true));

        return array($fields, $hidden);
    }

    /**
     * Standard crud_module submitter getter.
     *
     * @param  ID_TEXT                  $id The entry for which the submitter is sought
     * @return array                    The submitter, and the time of submission (null submission time implies no known submission time)
     */
    public function get_submitter($id)
    {
        $rows = $GLOBALS['SITE_DB']->query_select('tutorials_external', array('t_submitter', 't_add_date'), array('id' => intval($id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return array(null, null);
        }
        return array($rows[0]['t_submitter'], $rows[0]['t_add_date']);
    }

    /**
     * Standard crud_module edit form filler.
     *
     * @param  ID_TEXT                  $id The entry being edited
     * @return array                    A pair: the tempcode for the visible fields, and the tempcode for the hidden fields
     */
    public function fill_in_edit_form($id)
    {
        $rows = $GLOBALS['SITE_DB']->query_select('tutorials_external', array('*'), array('id' => intval($id)));
        if (!array_key_exists(0, $rows)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $myrow = $rows[0];

        $tags = collapse_1d_complexity('t_tag', $GLOBALS['SITE_DB']->query_select('tutorials_external_tags', array('t_tag'), array(
            't_id' => intval($id),
        )));

        return $this->get_form_fields($myrow['id'], $myrow['t_url'], $myrow['t_title'], $myrow['t_summary'], $myrow['t_icon'], $myrow['t_media_type'], $myrow['t_difficulty_level'], $myrow['t_pinned'], $myrow['t_author'], $tags);
    }

    /**
     * Standard crud_module add actualiser.
     *
     * @return ID_TEXT                  The entry added
     */
    public function add_actualisation()
    {
        $url = post_param('url');
        $title = post_param('title');
        $summary = post_param('summary');
        $icon = find_theme_image(post_param('icon'));
        $media_type = post_param('media_type');
        $difficulty_level = post_param('difficulty_level');
        $pinned = post_param_integer('pinned', 0);
        if (!$GLOBALS['FORUM_DRIVER']->is_staff(get_member())) {
            $pinned = 0;
        }
        $author = post_param('author');
        $tags = empty($_POST['tags']) ? array() : $_POST['tags'];

        $id = $GLOBALS['SITE_DB']->query_insert('tutorials_external', array(
            't_url' => $url,
            't_title' => $title,
            't_summary' => $summary,
            't_icon' => $icon,
            't_media_type' => $media_type,
            't_difficulty_level' => $difficulty_level,
            't_pinned' => $pinned,
            't_author' => $author,
            't_submitter' => get_member(),
            't_views' => 0,
            't_add_date' => time(),
            't_edit_date' => time(),
        ), true);

        foreach ($tags as $tag) {
            $GLOBALS['SITE_DB']->query_insert('tutorials_external_tags', array(
                't_id' => $id,
                't_tag' => $tag,
            ));
        }

        return strval($id);
    }

    /**
     * Standard crud_module edit actualiser.
     *
     * @param  ID_TEXT                  $_id The entry being edited
     */
    public function edit_actualisation($_id)
    {
        $id = intval($_id);

        $url = post_param('url');
        $title = post_param('title');
        $summary = post_param('summary');
        $icon = find_theme_image(post_param('icon'));
        $media_type = post_param('media_type');
        $difficulty_level = post_param('difficulty_level');
        $pinned = post_param_integer('pinned', 0);
        if (!$GLOBALS['FORUM_DRIVER']->is_staff(get_member())) {
            $pinned = 0;
        }
        $author = post_param('author');
        $tags = empty($_POST['tags']) ? array() : $_POST['tags'];

        $GLOBALS['SITE_DB']->query_update('tutorials_external', array(
            't_url' => $url,
            't_title' => $title,
            't_summary' => $summary,
            't_icon' => $icon,
            't_media_type' => $media_type,
            't_difficulty_level' => $difficulty_level,
            't_pinned' => $pinned,
            't_author' => $author,
            't_edit_date' => time(),
        ), array('id' => $id), '', 1);

        $GLOBALS['SITE_DB']->query_delete('tutorials_external_tags', array('t_id' => $id));
        foreach ($tags as $tag) {
            $GLOBALS['SITE_DB']->query_insert('tutorials_external_tags', array(
                't_id' => $id,
                't_tag' => $tag,
            ));
        }
    }

    /**
     * Standard crud_module delete actualiser.
     *
     * @param  ID_TEXT                  $_id The entry being deleted
     */
    public function delete_actualisation($_id)
    {
        $id = intval($_id);

        $GLOBALS['SITE_DB']->query_delete('tutorials_external', array('id' => $id), '', 1);
        $GLOBALS['SITE_DB']->query_delete('tutorials_external_tags', array('t_id' => $id));
    }
}