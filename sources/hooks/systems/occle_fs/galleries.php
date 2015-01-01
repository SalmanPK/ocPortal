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
 * @package    galleries
 */

require_code('resource_fs');

/**
 * Hook class.
 */
class Hook_occle_fs_galleries extends Resource_fs_base
{
    public $folder_resource_type = 'gallery';
    public $file_resource_type = array('image', 'video');

    /**
     * Standard occle_fs function for seeing how many resources are. Useful for determining whether to do a full rebuild.
     *
     * @param  ID_TEXT                  $resource_type The resource type
     * @return integer                  How many resources there are
     */
    public function get_resources_count($resource_type)
    {
        switch ($resource_type) {
            case 'image':
            case 'video':
                return $GLOBALS['SITE_DB']->query_select_value($resource_type . 's', 'COUNT(*)');

            case 'gallery':
                return $GLOBALS['SITE_DB']->query_select_value('galleries', 'COUNT(*)');
        }
        return 0;
    }

    /**
     * Standard occle_fs function for searching for a resource by label.
     *
     * @param  ID_TEXT                  $resource_type The resource type
     * @param  LONG_TEXT                $label The resource label
     * @return array                    A list of resource IDs
     */
    public function find_resource_by_label($resource_type, $label)
    {
        switch ($resource_type) {
            case 'image':
            case 'video':
                $_ret = $GLOBALS['SITE_DB']->query_select($resource_type . 's', array('id'), array($GLOBALS['SITE_DB']->translate_field_ref('title') => $label));
                $ret = array();
                foreach ($_ret as $r) {
                    $ret[] = strval($r['id']);
                }
                return $ret;

            case 'gallery':
                $ret = $GLOBALS['SITE_DB']->query_select('galleries', array('name'), array($GLOBALS['SITE_DB']->translate_field_ref('fullname') => $label));
                return collapse_1d_complexity('name', $ret);
        }
        return array();
    }

    /**
     * Standard occle_fs introspection function.
     *
     * @return array                    The properties available for the resource type
     */
    protected function _enumerate_folder_properties()
    {
        return array(
            'name' => 'ID_TEXT',
            'description' => 'LONG_TRANS',
            'notes' => 'LONG_TEXT',
            'accept_images' => 'BINARY',
            'accept_videos' => 'BINARY',
            'is_member_synched' => 'BINARY',
            'flow_mode_interface' => 'BINARY',
            'rep_image' => 'URLPATH',
            'watermark_top_left' => 'URLPATH',
            'watermark_top_right' => 'URLPATH',
            'watermark_bottom_left' => 'URLPATH',
            'watermark_bottom_right' => 'URLPATH',
            'allow_rating' => 'BINARY',
            'allow_comments' => 'SHORT_INTEGER',
            'add_date' => 'TIME',
            'owner' => 'member',
            'meta_keywords' => 'LONG_TRANS',
            'meta_description' => 'LONG_TRANS',
        );
    }

    /**
     * Standard occle_fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array                    $row Resource row (not full, but does contain the ID)
     * @return ?TIME                    The edit date or add date, whichever is higher (null: could not find one)
     */
    protected function _get_folder_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'adminlogs WHERE ' . db_string_equal_to('param_a', $row['name']) . ' AND  (' . db_string_equal_to('the_type', 'ADD_GALLERY') . ' OR ' . db_string_equal_to('the_type', 'EDIT_GALLERY') . ')';
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard occle_fs add function for resource-fs hooks. Adds some resource with the given label and properties.
     *
     * @param  LONG_TEXT                $filename Filename OR Resource label
     * @param  string                   $path The path (blank: root / not applicable)
     * @param  array                    $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT                 The resource ID (false: error)
     */
    public function folder_add($filename, $path, $properties)
    {
        list($category_resource_type, $category) = $this->folder_convert_filename_to_id($path);
        if ($category == '') {
            $category = 'root';
        }/*return false;*/ // Can't create more than one root

        list($properties, $label) = $this->_folder_magic_filter($filename, $path, $properties);

        require_code('galleries2');

        $name = $this->_default_property_str($properties, 'name'); // We don't use name for the label, although we do default name from label. Without other resource types we do use codenames as labels as often there is no other choice of label (even if there is a title, it's often optional).
        if ($name == '') {
            $name = $this->_create_name_from_label($label);
        }
        $description = $this->_default_property_str($properties, 'description');
        $notes = $this->_default_property_str($properties, 'notes');
        $parent_id = $category;
        $accept_images = $this->_default_property_int_modeavg($properties, 'accept_images', 'galleries', 1);
        $accept_videos = $this->_default_property_int_modeavg($properties, 'accept_videos', 'galleries', 1);
        $is_member_synched = $this->_default_property_int($properties, 'is_member_synched');
        $flow_mode_interface = $this->_default_property_int($properties, 'flow_mode_interface');
        $rep_image = $this->_default_property_str($properties, 'rep_image');
        $watermark_top_left = $this->_default_property_str($properties, 'watermark_top_left');
        $watermark_top_right = $this->_default_property_str($properties, 'watermark_top_right');
        $watermark_bottom_left = $this->_default_property_str($properties, 'watermark_bottom_left');
        $watermark_bottom_right = $this->_default_property_str($properties, 'watermark_bottom_right');
        $allow_rating = $this->_default_property_int_modeavg($properties, 'allow_rating', 'galleries', 1);
        $allow_comments = $this->_default_property_int_modeavg($properties, 'allow_comments', 'galleries', 1);
        $add_date = $this->_default_property_int_null($properties, 'add_date');
        $g_owner = $this->_default_property_int_null($properties, 'owner');
        $meta_keywords = $this->_default_property_str($properties, 'meta_keywords');
        $meta_description = $this->_default_property_str($properties, 'meta_description');
        $name = add_gallery($name, $label, $description, $notes, $parent_id, $accept_images, $accept_videos, $is_member_synched, $flow_mode_interface, $rep_image, $watermark_top_left, $watermark_top_right, $watermark_bottom_left, $watermark_bottom_right, $allow_rating, $allow_comments, false, $add_date, $g_owner, $meta_keywords, $meta_description, true);
        return $name;
    }

    /**
     * Standard occle_fs load function for resource-fs hooks. Finds the properties for some resource.
     *
     * @param  SHORT_TEXT               $filename Filename
     * @param  string                   $path The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
     * @return ~array                   Details of the resource (false: error)
     */
    public function folder_load($filename, $path)
    {
        list($resource_type, $resource_id) = $this->folder_convert_filename_to_id($filename);

        $rows = $GLOBALS['SITE_DB']->query_select('galleries', array('*'), array('name' => $resource_id), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        list($meta_keywords, $meta_description) = seo_meta_get_for($resource_type, strval($row['id']));

        return array(
            'label' => $row['fullname'],
            'name' => $row['name'],
            'description' => $row['description'],
            'notes' => $row['notes'],
            'accept_images' => $row['accept_images'],
            'accept_videos' => $row['accept_videos'],
            'is_member_synched' => $row['is_member_synched'],
            'flow_mode_interface' => $row['flow_mode_interface'],
            'rep_image' => $row['rep_image'],
            'watermark_top_left' => $row['watermark_top_left'],
            'watermark_top_right' => $row['watermark_top_right'],
            'watermark_bottom_left' => $row['watermark_bottom_left'],
            'watermark_bottom_right' => $row['watermark_bottom_right'],
            'allow_rating' => $row['allow_rating'],
            'allow_comments' => $row['allow_comments'],
            'add_date' => $row['add_date'],
            'owner' => $row['g_owner'],
            'meta_keywords' => $meta_keywords,
            'meta_description' => $meta_description,
        );
    }

    /**
     * Standard occle_fs edit function for resource-fs hooks. Edits the resource to the given properties.
     *
     * @param  ID_TEXT                  $filename The filename
     * @param  string                   $path The path (blank: root / not applicable)
     * @param  array                    $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT                 The resource ID (false: error, could not create via these properties / here)
     */
    public function folder_edit($filename, $path, $properties)
    {
        list($category_resource_type, $category) = $this->folder_convert_filename_to_id($path);
        list($resource_type, $resource_id) = $this->folder_convert_filename_to_id($filename);

        require_code('galleries2');

        $label = $this->_default_property_str($properties, 'label');
        $name = $this->_default_property_str_null($properties, 'name');
        if ($name === null) {
            $name = $this->_create_name_from_label($label);
        }
        $description = $this->_default_property_str($properties, 'description');
        $notes = $this->_default_property_str($properties, 'notes');
        $parent_id = $category;
        $accept_images = $this->_default_property_int_modeavg($properties, 'accept_images', 'galleries', 1);
        $accept_videos = $this->_default_property_int_modeavg($properties, 'accept_videos', 'galleries', 1);
        $is_member_synched = $this->_default_property_int($properties, 'is_member_synched');
        $flow_mode_interface = $this->_default_property_int($properties, 'flow_mode_interface');
        $rep_image = $this->_default_property_str($properties, 'rep_image');
        $watermark_top_left = $this->_default_property_str($properties, 'watermark_top_left');
        $watermark_top_right = $this->_default_property_str($properties, 'watermark_top_right');
        $watermark_bottom_left = $this->_default_property_str($properties, 'watermark_bottom_left');
        $watermark_bottom_right = $this->_default_property_str($properties, 'watermark_bottom_right');
        $allow_rating = $this->_default_property_int_modeavg($properties, 'allow_rating', 'galleries', 1);
        $allow_comments = $this->_default_property_int_modeavg($properties, 'allow_comments', 'galleries', 1);
        $add_time = $this->_default_property_int_null($properties, 'add_date');
        $g_owner = $this->_default_property_int_null($properties, 'owner');
        $meta_keywords = $this->_default_property_str($properties, 'meta_keywords');
        $meta_description = $this->_default_property_str($properties, 'meta_description');

        $name = edit_gallery($resource_id, $name, $label, $description, $notes, $parent_id, $accept_images, $accept_videos, $is_member_synched, $flow_mode_interface, $rep_image, $watermark_top_left, $watermark_top_right, $watermark_bottom_left, $watermark_bottom_right, $meta_keywords, $meta_description, $allow_rating, $allow_comments, $g_owner, $add_time, true, true);

        return $resource_id;
    }

    /**
     * Standard occle_fs delete function for resource-fs hooks. Deletes the resource.
     *
     * @param  ID_TEXT                  $filename The filename
     * @param  string                   $path The path (blank: root / not applicable)
     * @return boolean                  Success status
     */
    public function folder_delete($filename, $path)
    {
        list($resource_type, $resource_id) = $this->folder_convert_filename_to_id($filename);

        require_code('galleries2');
        delete_gallery($resource_id);

        return true;
    }

    /**
     * Standard occle_fs introspection function.
     *
     * @return array                    The properties available for the resource type
     */
    protected function _enumerate_file_properties()
    {
        return array(
            'description' => 'LONG_TRANS',
            'url' => 'URLPATH',
            'thumb_url' => 'URLPATH',
            'validated' => 'BINARY',
            'allow_rating' => 'BINARY',
            'allow_comments' => 'SHORT_INTEGER',
            'allow_trackbacks' => 'BINARY',
            'notes' => 'LONG_TEXT',
            'meta_keywords' => 'LONG_TRANS',
            'meta_description' => 'LONG_TRANS',
            'video_length' => 'INTEGER',
            'video_width' => 'INTEGER',
            'video_height' => 'INTEGER',
            'views' => 'INTEGER',
            'submitter' => 'member',
            'add_date' => 'TIME',
            'edit_date' => '?TIME',
        );
    }

    /**
     * Get the filename for a resource ID. Note that filenames are unique across all folders in a filesystem.
     *
     * @param  ID_TEXT                  $resource_type The resource type
     * @param  ID_TEXT                  $resource_id The resource ID
     * @return ID_TEXT                  The filename
     */
    public function file_convert_id_to_filename($resource_type, $resource_id)
    {
        if ($resource_type == 'video') {
            return 'VIDEO-' . parent::file_convert_id_to_filename($resource_type, $resource_id, 'video');
        }

        return parent::file_convert_id_to_filename($resource_type, $resource_id);
    }

    /**
     * Get the resource ID for a filename. Note that filenames are unique across all folders in a filesystem.
     *
     * @param  ID_TEXT                  $filename The filename, or filepath
     * @return array                    A pair: The resource type, the resource ID
     */
    public function file_convert_filename_to_id($filename)
    {
        if (substr($filename, 0, 6) == 'VIDEO-') {
            return parent::file_convert_filename_to_id(substr($filename, 6), 'video');
        }

        return parent::file_convert_filename_to_id($filename, 'image');
    }

    /**
     * Standard occle_fs add function for resource-fs hooks. Adds some resource with the given label and properties.
     *
     * @param  LONG_TEXT                $filename Filename OR Resource label
     * @param  string                   $path The path (blank: root / not applicable)
     * @param  array                    $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @param  ?ID_TEXT                 $force_type Resource type to try to force (null: do not force)
     * @return ~ID_TEXT                 The resource ID (false: error, could not create via these properties / here)
     */
    public function file_add($filename, $path, $properties, $force_type = null)
    {
        list($category_resource_type, $category) = $this->folder_convert_filename_to_id($path);
        list($properties, $label) = $this->_file_magic_filter($filename, $path, $properties);

        if (is_null($category)) {
            return false; // Folder not found
        }

        require_code('galleries2');

        $description = $this->_default_property_str($properties, 'description');
        $url = $this->_default_property_str($properties, 'url');
        $thumb_url = $this->_default_property_str($properties, 'thumb_url');
        $validated = $this->_default_property_int_null($properties, 'validated');
        if (is_null($validated)) {
            $validated = 1;
        }
        $notes = $this->_default_property_str($properties, 'notes');
        $submitter = $this->_default_property_int_null($properties, 'submitter');
        $add_date = $this->_default_property_int_null($properties, 'add_date');
        $edit_date = $this->_default_property_int_null($properties, 'edit_date');
        $views = $this->_default_property_int($properties, 'views');
        $meta_keywords = $this->_default_property_str($properties, 'meta_keywords');
        $meta_description = $this->_default_property_str($properties, 'meta_description');

        require_code('images');
        if ((((is_image($url)) || ($url == '')) && ((!array_key_exists('video_length', $properties)) || ($properties['video_length'] == '')) || ($force_type === 'image')) && ($force_type !== 'video')) {
            $allow_rating = $this->_default_property_int_modeavg($properties, 'allow_rating', 'images', 1);
            $allow_comments = $this->_default_property_int_modeavg($properties, 'allow_comments', 'images', 1);
            $allow_trackbacks = $this->_default_property_int_modeavg($properties, 'allow_trackbacks', 'images', 1);

            $accept_images = $GLOBALS['SITE_DB']->query_select_value_if_there('galleries', 'accept_images', array('name' => $category));
            if ($accept_images === 0) {
                return false;
            }

            $id = add_image($label, $category, $description, $url, $thumb_url, $validated, $allow_rating, $allow_comments, $allow_trackbacks, $notes, $submitter, $add_date, $edit_date, $views, null, $meta_keywords, $meta_description);
        } else {
            $allow_rating = $this->_default_property_int_modeavg($properties, 'allow_rating', 'videos', 1);
            $allow_comments = $this->_default_property_int_modeavg($properties, 'allow_comments', 'videos', 1);
            $allow_trackbacks = $this->_default_property_int_modeavg($properties, 'allow_trackbacks', 'videos', 1);

            $accept_videos = $GLOBALS['SITE_DB']->query_select_value_if_there('galleries', 'accept_videos', array('name' => $category));
            if ($accept_videos === 0) {
                return false;
            }

            $video_length = $this->_default_property_int($properties, 'video_length');
            $video_width = $this->_default_property_int_null($properties, 'video_width');
            if (is_null($video_width)) {
                $video_width = 720;
            }
            $video_height = $this->_default_property_int_null($properties, 'video_height');
            if (is_null($video_height)) {
                $video_height = 576;
            }

            $id = add_video($label, $category, $description, $url, $thumb_url, $validated, $allow_rating, $allow_comments, $allow_trackbacks, $notes, $video_length, $video_width, $video_height, $submitter, $add_date, $edit_date, $views, null, $meta_keywords, $meta_description);
        }

        return strval($id);
    }

    /**
     * Standard occle_fs load function for resource-fs hooks. Finds the properties for some resource.
     *
     * @param  SHORT_TEXT               $filename Filename
     * @param  string                   $path The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
     * @return ~array                   Details of the resource (false: error)
     */
    public function file_load($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        $rows = $GLOBALS['SITE_DB']->query_select($resource_type . 's', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        list($meta_keywords, $meta_description) = seo_meta_get_for($resource_type, strval($row['id']));

        $ret = array(
            'label' => $row['title'],
            'description' => $row['description'],
            'url' => $row['url'],
            'thumb_url' => $row['thumb_url'],
            'validated' => $row['validated'],
            'allow_rating' => $row['allow_rating'],
            'allow_comments' => $row['allow_comments'],
            'allow_trackbacks' => $row['allow_trackbacks'],
            'notes' => $row['notes'],
            'meta_keywords' => $meta_keywords,
            'meta_description' => $meta_description,
            'submitter' => $row['submitter'],
            'add_date' => $row['add_date'],
            'edit_date' => $row['edit_date'],
        );
        if ($resource_type == 'video') {
            $ret += array(
                'views' => $row['video_views'],
                'video_length' => $row['video_length'],
                'video_width' => $row['video_width'],
                'video_height' => $row['video_height'],
            );
        } else {
            $ret += array(
                'views' => $row['image_views'],
            );
        }
        return $ret;
    }

    /**
     * Standard occle_fs edit function for resource-fs hooks. Edits the resource to the given properties.
     *
     * @param  ID_TEXT                  $filename The filename
     * @param  string                   $path The path (blank: root / not applicable)
     * @param  array                    $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT                 The resource ID (false: error, could not create via these properties / here)
     */
    public function file_edit($filename, $path, $properties)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);
        list($category_resource_type, $category) = $this->folder_convert_filename_to_id($path);
        list($properties,) = $this->_file_magic_filter($filename, $path, $properties);

        if (is_null($category)) {
            return false; // Folder not found
        }

        require_code('galleries2');

        $label = $this->_default_property_str($properties, 'label');
        $description = $this->_default_property_str($properties, 'description');
        $url = $this->_default_property_str($properties, 'url');
        $thumb_url = $this->_default_property_str($properties, 'thumb_url');
        $validated = $this->_default_property_int_null($properties, 'validated');
        if (is_null($validated)) {
            $validated = 1;
        }
        $notes = $this->_default_property_str($properties, 'notes');
        $submitter = $this->_default_property_int_null($properties, 'submitter');
        $add_time = $this->_default_property_int_null($properties, 'add_date');
        $edit_time = $this->_default_property_int_null($properties, 'edit_date');
        $views = $this->_default_property_int($properties, 'views');
        $meta_keywords = $this->_default_property_str($properties, 'meta_keywords');
        $meta_description = $this->_default_property_str($properties, 'meta_description');

        if ($resource_type == 'image') {
            $allow_rating = $this->_default_property_int_modeavg($properties, 'allow_rating', 'images', 1);
            $allow_comments = $this->_default_property_int_modeavg($properties, 'allow_comments', 'images', 1);
            $allow_trackbacks = $this->_default_property_int_modeavg($properties, 'allow_trackbacks', 'images', 1);

            $accept_images = $GLOBALS['SITE_DB']->query_select_value_if_there('galleries', 'accept_images', array('name' => $category));
            if ($accept_images === 0) {
                return false;
            }

            edit_image(intval($resource_id), $label, $category, $description, $url, $thumb_url, $validated, $allow_rating, $allow_comments, $allow_trackbacks, $notes, $meta_keywords, $meta_description, $edit_time, $add_time, $views, $submitter, true);
        } else {
            $allow_rating = $this->_default_property_int_modeavg($properties, 'allow_rating', 'videos', 1);
            $allow_comments = $this->_default_property_int_modeavg($properties, 'allow_comments', 'videos', 1);
            $allow_trackbacks = $this->_default_property_int_modeavg($properties, 'allow_trackbacks', 'videos', 1);

            $accept_videos = $GLOBALS['SITE_DB']->query_select_value_if_there('galleries', 'accept_videos', array('name' => $category));
            if ($accept_videos === 0) {
                return false;
            }

            $video_length = $this->_default_property_int($properties, 'video_length');
            $video_width = $this->_default_property_int_null($properties, 'video_width');
            if (is_null($video_width)) {
                $video_width = 720;
            }
            $video_height = $this->_default_property_int_null($properties, 'video_height');
            if (is_null($video_height)) {
                $video_height = 576;
            }

            edit_video(intval($resource_id), $label, $category, $description, $url, $thumb_url, $validated, $allow_rating, $allow_comments, $allow_trackbacks, $notes, $video_length, $video_width, $video_height, $meta_keywords, $meta_description, $edit_time, $add_time, $views, $submitter, true);
        }

        return $resource_id;
    }

    /**
     * Standard occle_fs delete function for resource-fs hooks. Deletes the resource.
     *
     * @param  ID_TEXT                  $filename The filename
     * @param  string                   $path The path (blank: root / not applicable)
     * @return boolean                  Success status
     */
    public function file_delete($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        require_code('galleries2');
        require_code('images');
        if ($resource_type == 'image') {
            delete_image(intval($resource_id));
        } else {
            delete_video(intval($resource_id));
        }

        return true;
    }
}
