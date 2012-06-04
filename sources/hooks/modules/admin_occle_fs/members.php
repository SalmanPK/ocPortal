<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_ocf
 */

class Hook_members
{
	/**
	* Standard modular listing function for OcCLE FS hooks.
	*
	* @param  array	The current meta-directory path
	* @param  string  The root node of the current meta-directory
	* @param  array	The current directory listing
	* @param  array	A reference to the OcCLE filesystem object
	* @return ~array  The final directory listing (false: failure)
	*/
	function listing($meta_dir,$meta_root_node,$current_dir,&$occle_fs)
	{
		if (get_forum_type()!='ocf') return false;

		$listing=array();
		if (count($meta_dir)<1)
		{
			//We're listing the users
			$cnt=$GLOBALS['SITE_DB']->query_value('f_members','COUNT(*)');
			if ($cnt>1000) return false; // Too much to process

			$users=$GLOBALS['SITE_DB']->query_select('f_members',array('m_username'));
			foreach ($users as $user) $listing[$user['m_username']]=array();
		}
		elseif (count($meta_dir)==1)
		{
			//We're listing the profile fields and custom profile fields of the specified member
			$username=$meta_dir[0];
			$member_data=$GLOBALS['SITE_DB']->query_select('f_members',array('*'),array('m_username'=>$username),'',1);
			if (!array_key_exists(0,$member_data)) return false;
			$member_data=$member_data[0];
			$listing=array(
				'theme',
				'avatar',
				'validated',
				'timezone_offset',
				'primary_group',
				'signature',
				'banned',
				'preview_posts',
				'dob_day',
				'dob_month',
				'dob_year',
				'reveal_age',
				'e-mail',
				'title',
				'photo',
				'photo_thumb',
				'view_signatures',
				'auto_monitor_contrib_content',
				'language',
				'allow_e-mails',
				'notes',
				'wide',
				'max_attach_size'
			);
			$listing['groups']=array();

			//Custom profile fields
			$member_custom_fields=$GLOBALS['SITE_DB']->query_select('f_member_custom_fields',array('*'),array('mf_member_id'=>$member_data['id']));
			if (!array_key_exists(0,$member_custom_fields)) return false;
			$member_custom_fields=$member_custom_fields[0];

			$i=db_get_first_id();

			while (array_key_exists('field_'.strval($i),$member_custom_fields))
			{
				if ($member_custom_fields['field_'.strval($i)]!='')
					$listing[]=get_translated_text($GLOBALS['SITE_DB']->query_value('f_custom_fields','cf_name',array('id'=>$i)));
				$i++;
			}
		}
		elseif (count($meta_dir)==2)
		{
			if ($meta_dir[1]!='groups') return false;

			//List the member's usergroups
			$groups=$GLOBALS['FORUM_DRIVER']->get_members_groups($GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0]));
			$group_names=$GLOBALS['FORUM_DRIVER']->get_usergroup_list();
			foreach ($groups as $group)
			{
				if (array_key_exists($group,$group_names)) $listing[]=$group_names[$group];
			}
		}
		else return false; // Directory doesn't exist

		return $listing;
	}

	/**
	* Standard modular directory creation function for OcCLE FS hooks.
	*
	* @param  array		The current meta-directory path
	* @param  string		The root node of the current meta-directory
	* @param  string		The new directory name
	* @param  array		A reference to the OcCLE filesystem object
	* @return boolean		Success?
	*/
	function make_directory($meta_dir,$meta_root_node,$new_dir_name,&$occle_fs)
	{
		if (get_forum_type()!='ocf') return false;

		if (count($meta_dir)<1)
		{
			//We're at the top level, and adding a new member
			require_code('ocf_members_action');
			require_code('ocf_members_action2');
			ocf_make_member($new_dir_name,'occle','',NULL,NULL,NULL,NULL,array(),NULL,NULL,1,NULL,NULL,'','','',0,1,1,'','','',1,1,NULL,1,1,'',NULL,'',false);
		}
		else return false; //Directories aren't allowed to be added anywhere else

		return true;
	}

	/**
	* Standard modular directory removal function for OcCLE FS hooks.
	*
	* @param  array	The current meta-directory path
	* @param  string	The root node of the current meta-directory
	* @param  string	The directory name
	* @param  array	A reference to the OcCLE filesystem object
	* @return boolean	Success?
	*/
	function remove_directory($meta_dir,$meta_root_node,$dir_name,&$occle_fs)
	{
		if (get_forum_type()!='ocf') return false;

		if (count($meta_dir)<1)
		{
			//We're at the top level, and removing a member
			require_code('ocf_members_action');
			require_code('ocf_members_action2');
			ocf_delete_member($GLOBALS['FORUM_DRIVER']->get_member_from_username($dir_name));
		}
		else return false; //Directories aren't allowed to be removed anywhere else

		return true;
	}

	/**
	* Standard modular file removal function for OcCLE FS hooks.
	*
	* @param  array	The current meta-directory path
	* @param  string	The root node of the current meta-directory
	* @param  string	The file name
	* @param  array	A reference to the OcCLE filesystem object
	* @return boolean	Success?
	*/
	function remove_file($meta_dir,$meta_root_node,$file_name,&$occle_fs)
	{
		if (get_forum_type()!='ocf') return false;

		if (count($meta_dir)==1)
		{
			//We're in a member's directory, and deleting one of their profile fields
			if (in_array($file_name,array(
				'theme',
				'avatar',
				'validated',
				'timezone_offset',
				'primary_group',
				'signature',
				'banned',
				'preview_posts',
				'dob_day',
				'dob_month',
				'dob_year',
				'reveal_age',
				'e-mail',
				'title',
				'photo',
				'photo_thumb',
				'view_signatures',
				'auto_monitor_contrib_content',
				'language',
				'allow_e-mails',
				'notes',
				'wide',
				'max_attach_size'
			))) return false; //Can't delete a hard-coded (non-custom) profile field

			require_code('ocf_members_action');
			require_code('ocf_members_action2');
			$field_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_custom_fields f LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON t.id=f.cf_name','f.id',array('text_original'=>$file_name));
			if (is_null($field_id)) // Should never happen, but sometimes on upgrades/corruption...
			{
				$GLOBALS['FORUM_DRIVER']->install_create_custom_field($file_name,10);
				$field_id=$GLOBALS['FORUM_DB']->query_value('f_custom_fields f LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON t.id=f.cf_name','f.id',array('text_original'=>$file_name));
			}

			ocf_delete_custom_field($field_id);
		}
		elseif (count($meta_dir)==2)
		{
			if ($meta_dir[1]!='groups') return false;

			//We're in a member's usergroup directory, and removing them from a usergroup
			require_code('ocf_groups_action');
			require_code('ocf_groups_action2');
			$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list();
			$group_id=array_search($file_name,$groups);
			if ($group_id!==false) ocf_member_leave_group($group_id,$GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0]));
			else return false;
		}
		else return false; //Files shouldn't even exist anywhere else!

		return true;
	}

	/**
	* Standard modular file reading function for OcCLE FS hooks.
	*
	* @param  array		The current meta-directory path
	* @param  string		The root node of the current meta-directory
	* @param  string		The file name
	* @param  array		A reference to the OcCLE filesystem object
	* @return ~string	 	The file contents (false: failure)
	*/
	function read_file($meta_dir,$meta_root_node,$file_name,&$occle_fs)
	{
		if (get_forum_type()!='ocf') return false;

		if (count($meta_dir)==1)
		{
			//We're in a member's directory, and reading one of their profile fields
			$coded_fields=array(
				'theme'=>'m_theme',
				'avatar'=>'m_avatar_url',
				'validated'=>'m_validated',
				'timezone_offset'=>'m_timezone_offset',
				'primary_group'=>'m_primary_group',
				'signature'=>'m_signature',
				'banned'=>'m_is_perm_banned',
				'preview_posts'=>'m_preview_posts',
				'dob_day'=>'m_dob_day',
				'dob_month'=>'m_dob_month',
				'dob_year'=>'m_dob_year',
				'reveal_age'=>'m_reveal_age',
				'e-mail'=>'m_email_address',
				'title'=>'m_title',
				'photo'=>'m_photo_url',
				'photo_thumb'=>'m_photo_thumb_url',
				'view_signatures'=>'m_views_signatures',
				'auto_monitor_contrib_content'=>'m_auto_monitor_contrib_content',
				'language'=>'m_language',
				'allow_e-mails'=>'m_allow_emails',
				'allow_e-mails_from_staff'=>'m_allow_emails_from_staff',
				'notes'=>'m_notes',
				'wide'=>'m_zone_wide',
				'max_attach_size'=>'m_max_email_attach_size_mb'
			);
			if (array_key_exists($file_name,$coded_fields)) return $GLOBALS['FORUM_DB']->query_value_null_ok('f_members',$coded_fields[$file_name],array('id'=>$GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0])));

			require_code('ocf_members');
			$fields=ocf_get_custom_fields_member($GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0]));

			require_code('ocf_members_action');
			require_code('ocf_members_action2');
			$field_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_custom_fields f LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON t.id=f.cf_name','f.id',array('text_original'=>$file_name));
			if (is_null($field_id)) // Should never happen, but sometimes on upgrades/corruption...
			{
				$GLOBALS['FORUM_DRIVER']->install_create_custom_field($file_name,10);
				$field_id=$GLOBALS['FORUM_DB']->query_value('f_custom_fields f LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON t.id=f.cf_name','f.id',array('text_original'=>$file_name));
			}

			if (array_key_exists($field_id,$fields)) return $fields[$field_id];
			else return false;
		}
		elseif (count($meta_dir)==2)
		{
			if ($meta_dir[1]!='groups') return false;

			//We're in a member's usergroup directory, and all files should contain '1' :)
			return '1';
		}

		return false; //Files shouldn't even exist anywhere else!
	}

	/**
	* Standard modular file writing function for OcCLE FS hooks.
	*
	* @param  array	The current meta-directory path
	* @param  string	The root node of the current meta-directory
	* @param  string	The file name
	* @param  string	The new file contents
	* @param  array	A reference to the OcCLE filesystem object
	* @return boolean	Success?
	*/
	function write_file($meta_dir,$meta_root_node,$file_name,$contents,&$occle_fs)
	{
		if (get_forum_type()!='ocf') return false;

		if (count($meta_dir)==1)
		{
			//We're in a member's directory, and writing one of their profile fields
			$coded_fields=array(
				'theme'=>'m_theme',
				'avatar'=>'m_avatar_url',
				'validated'=>'m_validated',
				'timezone_offset'=>'m_timezone_offset',
				'primary_group'=>'m_primary_group',
				'signature'=>'m_signature',
				'banned'=>'m_is_perm_banned',
				'preview_posts'=>'m_preview_posts',
				'dob_day'=>'m_dob_day',
				'dob_month'=>'m_dob_month',
				'dob_year'=>'m_dob_year',
				'reveal_age'=>'m_reveal_age',
				'e-mail'=>'m_email_address',
				'title'=>'m_title',
				'photo'=>'m_photo_url',
				'photo_thumb'=>'m_photo_thumb_url',
				'view_signatures'=>'m_views_signatures',
				'auto_monitor_contrib_content'=>'m_auto_monitor_contrib_content',
				'language'=>'m_language',
				'allow_e-mails'=>'m_allow_emails',
				'allow_e-mails_from_staff'=>'m_allow_emails_from_staff',
				'notes'=>'m_notes',
				'wide'=>'m_zone_wide',
				'max_attach_size'=>'m_max_email_attach_size_mb',
				'highlighted_name'=>'m_highlighted_name',
			);
			if (array_key_exists($file_name,$coded_fields))
			{
				$GLOBALS['FORUM_DB']->query_update('f_members',array($coded_fields[$file_name]=>$contents),array('id'=>$GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0])),'',1);
				return true;
			}
			require_code('ocf_members_action');
			require_code('ocf_members_action2');
			$field_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_custom_fields f LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON t.id=f.cf_name','f.id',array('text_original'=>$file_name));
			if (is_null($field_id)) // Should never happen, but sometimes on upgrades/corruption...
			{
				$GLOBALS['FORUM_DRIVER']->install_create_custom_field($file_name,10);
				$field_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_custom_fields f LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON t.id=f.cf_name','f.id',array('text_original'=>$file_name));
			}

			if (is_null($field_id)) $field_id=ocf_make_custom_field($file_name);
			ocf_set_custom_field($GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0]),$field_id,$contents);
		}
		elseif (count($meta_dir)==2)
		{
			if ($meta_dir[1]!='groups') return false;

			//We're in a member's usergroup directory, and writing one of their usergroup associations
			require_code('ocf_groups_action');
			require_code('ocf_groups_action2');
			$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list();
			$group_id=array_search($file_name,$groups);
			if ($group_id!==false) ocf_add_member_to_group($GLOBALS['FORUM_DRIVER']->get_member_from_username($meta_dir[0]),$group_id,1);
			else return false;
		}
		else return false; //Group files can't be written, and other files shouldn't even exist anywhere else!

		return true;
	}

}

