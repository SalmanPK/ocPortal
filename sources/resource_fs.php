<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		occle
 */

/*
Resource-fs serves the 'var' parts of OcCLE-fs. It binds OcCLE-fs to a property/XML-based content model.

A programmer can also directly talk to Resource-fs to do abstracted CRUD operations on just about any kind of ocPortal resource.
i.e. Perform generalised operations on resource types without needing to know their individual APIs.

The user knows all of OcCLE-fs as "The ocPortal Repository".
*/

/**
 * Standard code module initialisation function.
 */
function init__resource_fs()
{
	require_code('occle');
}

/**
 * Generate, and save, a resource-fs moniker.
 *
 * @param  ID_TEXT		The resource type
 * @param  ID_TEXT		The resource ID
 * @param  ?SHORT_TEXT	The (new) label (NULL: lookup for specified resource)
 * @return array			A triple: The moniker (may be new, or the prior one if the moniker did not need to change), the GUID, the label
 */
function generate_resourcefs_moniker($resource_type,$resource_id,$label=NULL)
{
	$resource_object=get_content_object($resource_type);
	$resource_info=$resource_object->info();
	$resourcefs_hook=$resource_info['occle_filesystem_hook'];

	$no_exists_check_for=$GLOBALS['SITE_DB']->query_select_value_if_there('alternative_ids','resource_id',array('resource_type'=>$resource_type,'resource_id'=>$resource_id));

	if (is_null($label))
	{
		require_code('content');
		list($label)=content_get_details($resource_type,$resource_id);
	}

	require_code('urls2');
	$moniker=_generate_moniker($label);

	// Check it does not already exist
	$moniker_origin=$moniker;
	$next_num=1;
	if (is_numeric($moniker)) $moniker.='_1';
	$test=mixed();
	do
	{
		if (!is_null($no_exists_check_for))
		{
			if ($moniker==$no_exists_check_for) return $moniker; // This one is okay, we know it is safe
		}

		$test=$GLOBALS['SITE_DB']->query_select_value_if_there('alternative_ids','resource_id',array('resource_resourcefs_hook'=>$resourcefs_hook,'resource_moniker'=>$moniker));
		if (!is_null($test)) // Oh dear, will pass to next iteration, but trying a new moniker
		{
			$next_num++;
			$moniker=$moniker_origin.'_'.strval($next_num);
		}
	}
	while (!is_null($test));

	$guid=$GLOBALS['SITE_DB']->query_select_value_if_there('alternative_ids','resource_guid',array('resource_type'=>$resource_type,'resource_id'=>$resource_id));
	if (is_null($guid)) $guid=generate_guid();

	if ($moniker!==$no_exists_check_for)
	{
		$GLOBALS['SITE_DB']->query_delete('alternative_ids',array('resource_type'=>$resource_type,'resource_id'=>$resource_id),'',1);

		$GLOBALS['SITE_DB']->query_insert('alternative_ids',array(
			'resource_type'=>$resource_type,
			'resource_id'=>$resource_id,
			'resource_moniker'=>$moniker,
			'resource_label'=>$label,
			'resource_guid'=>$guid,
			'resource_resourcefs_hook'=>$resourcefs_hook,
		));
	}

	return array($moniker,$guid,$label);
}

/**
 * Generate, and save, a resource-fs moniker.
 *
 * @param  ID_TEXT		The resource type
 * @param  ID_TEXT		The resource ID
 */
function expunge_resourcefs_moniker($resource_type,$resource_id)
{
	$GLOBALS['SITE_DB']->query_delete('alternative_ids',array('resource_type'=>$resource_type,'resource_id'=>$resource_id),'',1);
}

/**
 * Find the resource GUID from the resource ID.
 *
 * @param  ID_TEXT	The resource type
 * @param  ID_TEXT	The resource ID
 * @return ?ID_TEXT	The GUID (NULL: no match)
 */
function find_guid_via_id($resource_type,$resource_id)
{
	return $GLOBALS['SITE_DB']->query_select_value_if_there('alternative_ids','resource_guid',array(
		'resource_type'=>$resource_type,
		'resource_id'=>$resource_id,
	));
}

/**
 * Find the OcCLE-fs (repository) filename from the resource ID.
 *
 * @param  ID_TEXT		The resource type
 * @param  ID_TEXT		The resource ID
 * @param  boolean		Whether to include the subpath
 * @return ?ID_TEXT		The filename (NULL: no match)
 */
function find_occlefs_filename_via_id($resource_type,$resource_id,$include_subpath=false)
{
	$moniker=$GLOBALS['SITE_DB']->query_select_value_if_there('alternative_ids','resource_moniker',array(
		'resource_type'=>$resource_type,
		'resource_id'=>$resource_id,
	));
	if (!is_null($moniker))
	{
		if ($include_subpath)
		{
			$occlefs_ob=get_resource_occlefs_object($resource_type);
			$subpath=$occlefs_ob->search($resource_type,$resource_id);
			$moniker=$subpath.'/'.$moniker;
		}
		return $moniker.'.xml';
	}
	return NULL;
}

/**
 * Find the resource label from the resource ID.
 *
 * @param  ID_TEXT		The resource type
 * @param  ID_TEXT		The resource ID
 * @return ?SHORT_TEXT	The label (NULL: no match)
 */
function find_label_via_id($resource_type,$resource_id)
{
	return $GLOBALS['SITE_DB']->query_select_value_if_there('alternative_ids','resource_label',array(
		'resource_type'=>$resource_type,
		'resource_id'=>$resource_id,
	));
}

/**
 * Find the resource ID from the resource label.
 *
 * @param  ID_TEXT		The resource type
 * @param  SHORT_TEXT	The label
 * @param  ?LONG_TEXT	The subpath (NULL: don't care)
 * @return ?ID_TEXT		The ID (NULL: no match)
 */
function find_id_via_label($resource_type,$label,$subpath=NULL)
{
	$ids=$GLOBALS['SITE_DB']->query_select('alternative_ids',array('resource_id'),array(
		'resource_type'=>$resource_type,
		'resource_label'=>$resource_label,
	));
	$resource_ids=collapse_1d_complexity('resource_id',$ids);
	foreach ($resource_ids as $resource_id)
	{
		if ($subpath===NULL) return $resource_id; // Don't care about path

		// Check path
		$occlefs_ob=get_resource_occlefs_object($resource_type);
		$_subpath=$occlefs_ob->search($resource_type,$resource_id);
		if ($_subpath==$subpath) return $resource_id;
	}
	// No valid match
	return NULL;
}

/**
 * Find the resource ID from the resource GUID. It is assumed you as the programmer already know the resource-type.
 *
 * @param  ID_TEXT		The GUID
 * @return ?ID_TEXT		The ID (NULL: no match)
 */
function find_id_via_guid($guid)
{
	return $GLOBALS['SITE_DB']->query_select_value_if_there('alternative_ids','resource_id',array(
		'resource_guid'=>$resource_guid,
	));
}

/**
 * Find the resource IDs from the resource GUIDs. This is useful if you need to resolve many GUIDs at once during performant-critical code.
 *
 * @param  array			The GUIDs
 * @return array			Mapping between GUIDs and IDs (anything where there's no match will result in no array entry being present for that GUID)
 */
function find_ids_via_guids($guids)
{
	$or_list='';
	foreach ($guids as $guid)
	{
		if ($or_list!='') $or_list.=' OR ';
		$or_list.=db_string_equal_to('resource_guid',$guid);
	}
	$query='SELECT resource_id,resource_guid FROM '.get_table_prefix().'alternative_ids WHERE '.$or_list;
	$ret=$GLOBALS['SITE_DB']->query($query,NULL,NULL,false,true);
	return collapse_2d_complexity('resource_id','resource_guid',$ret);
}

/**
 * Find the resource ID from the OcCLE-fs (repository) filename.
 *
 * @param  ID_TEXT		The resource type
 * @param  ID_TEXT		The filename
 * @return ?ID_TEXT		The ID (NULL: no match)
 */
function find_id_via_occlefs_filename($resource_type,$filename)
{
	$resource_filename=preg_replace('#^.*/#','',$resource_filename); // Paths not needed, as filenames are globally unique; paths would not be in alternative_ids table
	return $GLOBALS['SITE_DB']->query_select_value_if_there('alternative_ids','resource_id',array(
		'resource_type'=>$resource_type,
		'resource_moniker'=>basename($resource_filename,'.xml'),
	));
}

/**
 * Get the OccLE-fs object for a resource type.
 *
 * @param  ID_TEXT	The resource type
 * @return ?object	The object (NULL: could not get one)
 */
function get_resource_occlefs_object($resource_type)
{
	require_code('content');
	$object=get_content_object($resource_type);
	if (is_null($object)) return NULL;
	$info=$object->info();
	$fs_hook=$object->occle_filesystem_hook;
	if (is_null($fs_hook)) return NULL;

	require_code('hooks/systems/occle_fs/'.filter_naughty_harsh($resource_type));
	$fs_object=object_factory('Hook_occle_fs_'.filter_naughty_harsh($resource_type),true);
	if (is_null($fs_object)) return NULL;
	return $fs_object;
}

/**
 * Convert a local ID to something portable.
 *
 * @param  ID_TEXT	The resource type
 * @param  ID_TEXT	The resource ID
 * @return array		Portable ID details
 */
function remap_resource_id_as_portable($resource_type,$resource_id)
{
	list($moniker,$guid,$label)=generate_resourcefs_moniker($resource_type,$resource_id);

	$occlefs_ob=get_resource_occlefs_object($resource_type);
	$subpath=$occlefs_ob->search($resource_type,$resource_id);

	return array(
		'guid'=>$guid,
		'label'=>$label,
		'subpath'=>$subpath,
		//'moniker'=>$moniker,	Given more effectively with label
		'id'=>$resource_id // Not used, but useful to have anyway
	);
}

/**
 * Convert a portable ID to something local.
 *
 * @param  ID_TEXT	The resource type
 * @param  array		Portable ID details
 * @return ID_TEXT	The resource ID
 */
function remap_portable_as_resource_id($resource_type,$portable)
{
	//$resource_id=$portable['id'];	Would not be portable between sites

	$resource_id=find_id_via_guid($portable['guid']);
	if (!is_null($resource_id)) return $resource_id;

	$resource_id=find_id_via_label($portable['label'],$portable['subpath']);
	if (!is_null($resource_id)) return $resource_id;

	// Not found: Create
	$occlefs_ob=get_resource_occlefs_object($resource_type);
	$resource_id=$occlefs_ob->resource_add($resource_type,$portable['label'],$portable['subpath'],array());

	return $resource_id;
}

/**
 * Resource-fs base class.
 * @package		occle
 */
class resource_fs_base
{
	var $folder_resource_type=NULL;
	var $file_resource_type=NULL;
	var $_cma_object=array();

	/**
	 * Get the file resource info for this OcCLE-fs resource hook.
	 *
	 * @param  ID_TEXT	The resource type
	 * @return object		The object
	 */
	function _get_cma_info($resource_type)
	{
		if (!array_key_exists($resource_type,$this->_cma_object))
		{
			require_code('content');
			$this->_cma_object[$resource_type]=get_content_object($resource_type);
		}
		return $this->_cma_object[$resource_type]->info();
	}

	/**
	 * Find a default property, defaulting to blank.
	 *
	 * @param  array		The properties
	 * @param  ID_TEXT	The property
	 * @return ?string	The value (NULL: NULL value)
	 */
	function _default_property_str($properties,$property)
	{
		return array_key_exists($property,$properties)?$properties[$property]:'';
	}

	/**
	 * Find a default property, defaulting to NULL.
	 *
	 * @param  array		The properties
	 * @param  ID_TEXT	The property
	 * @return ?string	The value (NULL: NULL value)
	 */
	function _default_property_str_null($properties,$property)
	{
		return array_key_exists($property,$properties)?$properties[$property]:NULL;
	}

	/**
	 * Find a default property, defaulting to blank.
	 *
	 * @param  array		The properties
	 * @param  ID_TEXT	The property
	 * @return ?integer	The value (NULL: NULL value)
	 */
	function _default_property_int($properties,$property)
	{
		return array_key_exists($property,$properties)?intval($properties[$property]):0;
	}

	/**
	 * Find a default property, defaulting to blank.
	 *
	 * @param  array		The properties
	 * @param  ID_TEXT	The property
	 * @return ?integer	The value (NULL: NULL value)
	 */
	function _default_property_int_null($properties,$property)
	{
		return array_key_exists($property,$properties)?intval($properties[$property]):NULL;
	}

	/**
	 * Find a default property, defaulting to the average of what is there already, or the given default if really necessary.
	 *
	 * @param  array		The properties
	 * @param  ID_TEXT	The property
	 * @param  ID_TEXT	The table to average within
	 * @param  integer	The last-resort default
	 * @param  ?ID_TEXT	The database property (NULL: same as $property)
	 * @return integer	The value
	 */
	function _default_property_int_modeavg($properties,$property,$table,$default,$db_property=NULL)
	{
		if (is_null($db_property)) $db_property=$property;

		if (array_key_exists($property,$properties))
		{
			return intval($properties[$property]);
		}

		$db=$GLOBALS[(substr($table,0,2)=='f_')?'FORUM_DB':'SITE_DB'];
		$val=$db->query_value_if_there('SELECT '.$db_property.',count('.$db_property.') AS qty FROM '.get_table_prefix().$table.' GROUP BY '.$db_property.' ORDER BY qty DESC',false,true); // We need the mode here, not the mean
		if (!is_null($val)) return $val;

		return $default;
	}

	/**
	 * Find a default property, defaulting to blank.
	 *
	 * @param  ID_TEXT	The category value (blank: root)
	 * @return ?integer	The category (NULL: root)
	 */
	function _integer_category($category)
	{
		return ($category=='')?NULL:intval($category);
	}

	function is_folder_type($resource_type)
	{
		$folder_types=is_array($this->folder_resource_type)?$this->folder_resource_type:(is_null($this->folder_resource_type)?array():array($this->folder_resource_type));
		return in_array($resource_type,$folder_types);
	}

	function is_file_type($resource_type)
	{
		$file_types=is_array($this->file_resource_type)?$this->file_resource_type:(is_null($this->file_resource_type)?array():array($this->file_resource_type));
		return in_array($resource_type,$file_types);
	}

	function convert_label_to_filename($label,$subpath,$resource_type,$must_already_exist=false)
	{
		// TODO
	}

	function convert_filename_to_id($filename,$resource_type)
	{
		if ($this->is_file_type($resource_type))
			return $this->file_convert_filename_to_id($filename,$resource_type);
		if ($this->is_folder_type($resource_type))
			return $this->folder_convert_filename_to_id($filename,$resource_type);
		return NULL;
	}

	/**
	 * Get the resource ID for a filename. Note that filenames are unique across all folders in a filesystem.
	 *
	 * @param  ID_TEXT	The filename, or filepath
	 * @param  ?ID_TEXT	The resource type (NULL: assumption of only one folder resource type for this hook; only passed as non-NULL from overridden functions within hooks that are calling this as a helper function)
	 * @return ?array		A pair: The resource type, the resource ID (NULL: could not find)
	 */
	function file_convert_filename_to_id($filename,$resource_type=NULL)
	{
		if (is_null($resource_type)) $resource_type=$this->folder_resource_type;

		$resource_id=basename($filename,'.xml'); // Remove file extension from filename
		return array($resource_type,$resource_id);
	}

	/**
	 * Get the resource ID for a filename. Note that filenames are unique across all folders in a filesystem.
	 *
	 * @param  ID_TEXT	The filename, or filepath
	 * @param  ?ID_TEXT	The resource type (NULL: assumption of only one folder resource type for this hook; only passed as non-NULL from overridden functions within hooks that are calling this as a helper function)
	 * @return array		A pair: The resource type, the resource ID
	 */
	function folder_convert_filename_to_id($filename,$resource_type=NULL)
	{
		if (is_null($resource_type)) $resource_type=$this->folder_resource_type;

		// TODO. This is all wrong I think. And it must also recursively add new folders when it can't get a match.

		if ($filename=='<blank>') $filename='';
		$resource_id=basename($filename); // Get filename component from path
		return array($resource_type,$resource_id);
	}

	function convert_id_to_filename($filename,$resource_type)
	{
		if ($this->is_file_type($resource_type))
			return $this->file_convert_id_to_filename($filename,$resource_type);
		if ($this->is_folder_type($resource_type))
			return $this->folder_convert_id_to_filename($filename,$resource_type);
		return NULL;
	}

	/**
	 * Get the filename for a resource ID. Note that filenames are unique across all folders in a filesystem.
	 *
	 * @param  ID_TEXT	The resource type
	 * @param  ID_TEXT	The resource ID
	 * @return ID_TEXT	The filename
	 */
	function file_convert_id_to_filename($resource_type,$resource_id)
	{
		return $resource_id.'.xml';
	}

	/**
	 * Get the filename for a resource ID. Note that filenames are unique across all folders in a filesystem.
	 *
	 * @param  ID_TEXT	The resource type
	 * @param  ID_TEXT	The resource ID
	 * @return ?ID_TEXT	The filename (NULL: could not find)
	 */
	function folder_convert_id_to_filename($resource_type,$resource_id)
	{
		if ($resource_id=='') return '<blank>';
		return $resource_id;
	}

	/**
	 * Interpret the input of a folder, into a way we can understand it to add. Hooks may override this with special import code.
	 *
	 * @param  SHORT_TEXT	Filename OR Resource label
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			A pair: the resource label, Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 */
	function _folder_magic_filter($filename,$path,$properties)
	{
		return array($filename,$properties); // Default implementation is simply to assume the filename is the resource label, and leave properties alone
	}

	/**
	 * Interpret the input of a file, into a way we can understand it to add. Hooks may override this with special import code.
	 *
	 * @param  SHORT_TEXT	Filename OR Resource label
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			A pair: the resource label, Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 */
	function _file_magic_filter($filename,$path,$properties)
	{
		return array($filename,$properties); // Default implementation is simply to assume the filename is the resource label, and leave properties alone
	}

	function file_load($filename,$path)
	{
		// TODO call _file_load, but do post-processing
	}

	function folder_load($filename,$path)
	{
		// TODO call _folder_load, but do post-processing
	}

	function set_resource_access($filename,$groups)
	{
		// TODO
	}

	function get_resource_access($filename)
	{
		// TODO
	}

	function reset_resource_privileges($filename)
	{
		// TODO
	}

	function set_resource_privileges_from_preset($filename,$group_presets)
	{
		// TODO
	}

	function set_resource_privileges($filename,$group_settings)
	{
		// TODO
	}

	function get_resource_privileges($filename)
	{
		// TODO
	}

	function set_resource_privileges_from_preset__members($filename,$member_presets)
	{
		// TODO
	}

	function set_resource_privileges__members($filename,$privilege,$setting)
	{
		// TODO
	}

	function get_resource_privileges__members($filename)
	{
		// TODO
	}

	/**
	 * Whether the filesystem hook is active.
	 *
	 * @return boolean		Whether it is
	 */
	function _is_active()
	{
		return true;
	}

	/**
	 * Find whether a kind of resource handled by this hook (folder or file) can be under a particular kind of folder.
	 *
	 * @param  ID_TEXT		Folder resource type
	 * @param  ID_TEXT		Resource type (may be file or folder)
	 * @return boolean		Whether it can
	 */
	function _has_parent_child_relationship($above,$under)
	{
		return true;
	}

	/**
	 * Load up details for a resource dependency.
	 *
	 * @param  ID_TEXT		Resource type
	 * @param  ID_TEXT		Resource ID
	 * @return array			Details for the dependency
	 */
	function get_resource_dependency($resource_type,$resource_id)
	{
		// TODO
	}

	/**
	 * Load up details for a resource dependency.
	 *
	 * @param  array			Details for the dependency
	 * @return ID_TEXT		Resource ID
	 */
	function resolve_resource_dependency($details)
	{
		// TODO
	}

	/**
	 * Find all translated strings for a language ID.
	 *
	 * @param  AUTO_LINK		Language ID
	 * @return array			Details
	 */
	function get_translated_text($lang_id)
	{
		// TODO
	}

	/**
	 * Find meta keywords.
	 *
	 * @param  ID_TEXT		SEO type
	 * @param  ID_TEXT		ID
	 * @return string			Meta keywords
	 */
	function get_meta_keywords($seo_type,$id)
	{
		// TODO
	}

	/**
	 * Find a meta description.
	 *
	 * @param  ID_TEXT		SEO type
	 * @param  ID_TEXT		ID
	 * @return string			Meta description
	 */
	function get_meta_description($seo_type,$id)
	{
		// TODO
	}

	/**
	 * Adds some resource with the given label and properties. Wraps file_add/folder_add.
	 *
	 * @param  ID_TEXT		Resource type
	 * @param  SHORT_TEXT	Filename OR Resource label
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return ~ID_TEXT		The resource ID (false: error, could not create via these properties / here)
	 */
	function resource_add($resource_type,$label,$path,$properties)
	{
		if ($this->is_folder_type($resource_type))
		{
			$resource_id=$this->folder_add($label,$path,array());
		} else
		{
			$resource_id=$this->file_add($label,$path,array());
		}
		return $resource_id;
	}

	/**
	 * Finds the properties for some resource. Wraps file_load/folder_load.
	 *
	 * @param  ID_TEXT		Resource type
	 * @param  SHORT_TEXT	Filename
	 * @param  string			The path (blank: root / not applicable)
	 * @return ~array			Details of the resource (false: error)
	 */
	function resource_load($resource_type,$filename,$path)
	{
		if ($this->is_folder_type($resource_type))
		{
			$properties=folder_load($filename,$path);
		} else
		{
			$properties=file_load($filename,$path);
		}
		return $properties;
	}

	/**
	 * Edits the resource to the given properties. Wraps file_edit/folder_edit.
	 *
	 * @param  ID_TEXT		Resource type
	 * @param  ID_TEXT		The filename
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return boolean		Success status
	 */
	function resource_edit($resource_type,$filename,$path,$properties)
	{
		if ($this->is_folder_type($resource_type))
		{
			$status=folder_edit($filename,$path,$properties);
		} else
		{
			$status=file_edit($filename,$path,$properties);
		}
		return $status;
	}

	/**
	 * Standard modular delete function for OcCLE-fs resource hooks. Deletes the resource.
	 *
	 * @param  ID_TEXT		Resource type
	 * @param  ID_TEXT		The filename
	 * @return boolean		Success status
	 */
	function resource_delete($resource_type,$filename)
	{
		if ($this->is_folder_type($resource_type))
		{
			$status=folder_delete($filename);
		} else
		{
			$status=file_delete($filename);
		}
		return $status;
	}

	/**
	 * Standard modular listing function for OcCLE-fs hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  array		The current directory listing
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return ~array		The final directory listing (false: failure)
	 */
	function listing($meta_dir,$meta_root_node,$current_dir,&$occle_fs)
	{
		if (!$this->_is_active()) return false;

		$listing=array();

		$folder_types=is_array($this->folder_resource_type)?$this->folder_resource_type:(is_null($this->folder_resource_type)?array():array($this->folder_resource_type));
		$file_types=is_array($this->file_resource_type)?$this->file_resource_type:(is_null($this->file_resource_type)?array():array($this->file_resource_type));

		// Find where we're at
		$cat_id=mixed();
		$cat_resource_type=mixed();
		if (count($meta_dir)!=0)
		{
			if (is_null($this->folder_resource_type)) return false; // Should not be possible

			list($cat_resource_type,$_cat_id)=$this->file_convert_filename_to_id(implode('/',$meta_dir));
			$cat_id=($folder_info['id_field_numeric']?intval($_cat_id):$_cat_id)
		} else
		{
			if (!is_null($this->folder_resource_type))
			{
				$cat_id=($folder_info['id_field_numeric']?NULL:'');
				$cat_resource_type=is_array($this->folder_resource_type)?$this->folder_resource_type[0]:$this->folder_resource_type;
			}
		}

		// Find folders
		foreach ($folder_types as $resource_type)
		{
			if (!_has_parent_child_relationship($cat_resource_type,$resource_type)) continue;

			$folder_info=_get_cma_info($resource_type);
			$select=array('main.'.$folder_info['parent_spec__field_name']);
			$table=$folder_info['parent_spec__table_name'].' main';
			if ($folder_info['parent_spec__table_name']!=$folder_info['table'])
			{
				$table.=' JOIN '.$folder_info['table'].' cats ON cats.'.$folder_info['id_field'].'=main.'.$folder_info['parent_spec__table_name'];
				if (!is_null($folder_info['add_time_field'])) $select[]='cats.'.$folder_info['add_time_field'];
				if (!is_null($folder_info['edit_time_field'])) $select[]='cats.'.$folder_info['edit_time_field'];
			} else
			{
				if (!is_null($folder_info['add_time_field'])) $select[]=$folder_info['add_time_field'];
				if (!is_null($folder_info['edit_time_field'])) $select[]=$folder_info['edit_time_field'];
			}
			$extra='';
			if ((is_string($folder_info['id_field'])) && (can_arbitrary_groupby()))
				$extra.='GROUP BY '.$folder_info['id_field'].' '; // In case it's not a real category table, just an implied one by self-categorisation of entries
			$extra.='ORDER BY main.'.$folder_info['parent_spec__field_name'];
			$child_folders=$folder_info['connection']->query_select($table,$select,array('main.'.$folder_info['parent_category_field']=>$cat_id),$extra,10000/*Reasonable limit*/);
			foreach ($child_folders as $folder)
			{
				$file=$this->folder_convert_id_to_filename($resource_type,$folder[$folder_info['parent_spec__field_name']]);

				$filetime=mixed();
				if (method_exists($this,'_get_folder_edit_date'))
				{
					$filetime=$this->_get_folder_edit_date($folder,end($meta_dir));
				}
				if (is_null($filetime))
				{
					if (!is_null($folder_info['edit_time_field']))
					{
						$filetime=$folder[$folder_info['edit_time_field']];
					}
					if (is_null($filetime))
					{
						if (!is_null($folder_info['add_time_field']))
						{
							$filetime=$folder[$folder_info['add_time_field']];
						}
					}
				}

				$listing[]=array(
					$file,
					OCCLEFS_DIR,
					NULL/*don't calculate a filesize*/,
					$filetime,
				);
			}
		}

		// Find files
		foreach ($file_types as $resource_type)
		{
			if (!_has_parent_child_relationship($cat_resource_type,$resource_type)) continue;

			$file_info=_get_cma_info($resource_type);
			$where=array();
			if (!is_null($this->folder_resource_type))
			{
				$where[is_array($file_info['category_field'])?$file_info['category_field'][0]:$file_info['category_field']]=$cat_id;
			}
			$select=array();
			append_content_select_for_id($select,$file_info);
			if (!is_null($file_info['add_time_field'])) $select[]=$file_info['add_time_field'];
			if (!is_null($file_info['edit_time_field'])) $select[]=$file_info['edit_time_field'];
			$files=$file_info['connection']->query_select($file_info['table'],$select,$where,10000/*Reasonable limit*/);
			foreach ($files as $file)
			{
				$str_id=extract_content_str_id_from_data($file,$file_info);
				$file=$this->file_convert_id_to_filename($resource_type,$str_id);

				$filetime=mixed();
				if (method_exists($this,'_get_file_edit_date'))
				{
					$filetime=$this->_get_file_edit_date($file,end($meta_dir));
				}
				if (is_null($filetime))
				{
					if (!is_null($file_info['edit_time_field']))
					{
						$filetime=$file[$file_info['edit_time_field']];
					}
					if (is_null($filetime))
					{
						if (!is_null($file_info['add_time_field']))
						{
							$filetime=$file[$file_info['add_time_field']];
						}
					}
				}

				$listing[]=array(
					$file,
					OCCLEFS_FILE,
					NULL/*don't calculate a filesize*/,
					$filetime,
				);
			}
		}

		return $listing;
	}

	function search($resource_type,$resource_id)
	{
		// TODO, find subpath
	}

	/**
	 * Standard modular directory creation function for OcCLE-fs hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The new directory name
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function make_directory($meta_dir,$meta_root_node,$new_dir_name,&$occle_fs)
	{
		if (is_null($folder_resource_type)) return false;
_folder_add($label,$path,$properties)
		// TODO
	}

	/**
	 * Standard modular directory removal function for OcCLE-fs hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The directory name
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function remove_directory($meta_dir,$meta_root_node,$dir_name,&$occle_fs)
	{
		if (is_null($folder_resource_type)) return false;
folder_delete($path)
		// TODO
	}

	/**
	 * Standard modular file removal function for OcCLE-fs hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The file name
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function remove_file($meta_dir,$meta_root_node,$file_name,&$occle_fs)
	{
file_delete($path)
		// TODO
	}

	/**
	 * Standard modular file reading function for OcCLE-fs hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The file name
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return ~string	The file contents (false: failure)
	 */
	function read_file($meta_dir,$meta_root_node,$file_name,&$occle_fs)
	{
// TODO: We'll be given $properties, need to convert to XML
		// TODO
	}

	/**
	 * Standard modular file writing function for OcCLE-fs hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The file name
	 * @param  string		The new file contents
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function write_file($meta_dir,$meta_root_node,$file_name,$contents,&$occle_fs)
	{
// TODO: What if XML supplied? Need to parse into $properties
// TODO: Will check if there's something existing in the listing matching the exact name, if not will add (and the filename will actually change due to a new ID being assinged), if so will save into that
_file_add($filename,$path,$properties)
		// TODO
	}
}
