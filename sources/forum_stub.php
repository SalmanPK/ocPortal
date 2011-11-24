<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core
 */

/**
 * Standard code module initialisation function.
 */
function init__forum_stub()
{
	global $IS_SUPER_ADMIN_CACHE,$IS_STAFF_CACHE;
	$IS_SUPER_ADMIN_CACHE=array();
	$IS_STAFF_CACHE=array();
}

class forum_driver_base
{
	var $connection;

	var $MEMBER_ROWS_CACHED=array();

	/**
	 * Find the usergroup id of the forum guest member.
	 *
	 * @return GROUP			The usergroup id of the forum guest member
	 */
	function get_guest_group()
	{
		return db_get_first_id();
	}
	
	/**
	 * Get a URL to a forum member's member profile.
	 *
	 * @param  MEMBER			The forum member
	 * @param  boolean		Whether to be insistent that we go to the profile, rather than possibly starting an IM which can link to the profile
	 * @param  boolean		Whether it is okay to return the result using Tempcode (more efficient, and allows keep_* parameters to propagate which you almost certainly want!)
	 * @return mixed			The URL
	 */
	function member_profile_link($id,$definitely_profile=false,$tempcode_okay=false)
	{
		if ((!$definitely_profile) && ($id!=$this->get_guest_id()) && (addon_installed('chat')))
		{
			$username_click_im=get_option('username_click_im',true);
			if ($username_click_im=='1')
			{
				$url=build_url(array('page'=>'chat','type'=>'misc','enter_im'=>$id),get_module_zone('chat'));
				//if (is_object($url)) $url=$url->evaluate();
				return $url;
			}
		}

		$url=$this->_member_profile_link($id,$tempcode_okay);
		if ((get_forum_type()!='none') && (get_forum_type()!='ocf') && (get_option('forum_in_portal',true)=='1'))
		{
			$url=build_url(array('page'=>'forums','url'=>$url),get_module_zone('forums'));
			//if (is_object($url)) $url=$url->evaluate();
		}
		return $url;
	}

	/**
	 * Get a hyperlink (i.e. HTML link, not just a URL) to a forum member's member profile.
	 *
	 * @param  MEMBER			The forum member
	 * @param  boolean		Whether to be insistent that we go to the profile, rather than possibly starting an IM which can link to the profile
	 * @param  string			The username (blank: look it up)
	 * @return tempcode		The hyperlink
	 */
	function member_profile_hyperlink($id,$definitely_profile=false,$_username='')
	{
		if (is_guest($id))
			return ($_username=='')?do_lang_tempcode('GUEST'):make_string_tempcode(escape_html($_username));
		if ($_username=='') $_username=$this->get_username($id);
		if (is_null($_username))
			return do_lang_tempcode('UNKNOWN');
		$url=$this->member_profile_link($id,$definitely_profile,true);
		return hyperlink($url,$_username,false,true);
	}

	/**
	 * Get a URL to a forum join page.
	 *
	 * @return mixed			The URL
	 */
	function join_link()
	{
		$url=$this->_join_link();
		if ((get_forum_type()!='none') && (get_forum_type()!='ocf') && (get_option('forum_in_portal',true)=='1')) $url=build_url(array('page'=>'forums','url'=>$url),get_module_zone('forums'));
		return $url;
	}

	/**
	 * Get a URL to a forum 'user online' list.
	 *
	 * @return mixed			The URL
	 */
	function online_link()
	{
		$url=$this->_online_link();
		if ((get_forum_type()!='none') && (get_forum_type()!='ocf') && (get_option('forum_in_portal',true)=='1')) $url=build_url(array('page'=>'forums','url'=>$url),get_module_zone('forums'));
		return $url;
	}

	/**
	 * Get a URL to send a forum member a PM.
	 *
	 * @param  MEMBER			The forum member
	 * @return mixed			The URL
	 */
	function member_pm_link($id)
	{
		$url=$this->_member_pm_link($id);
		if ((get_forum_type()!='none') && (get_forum_type()!='ocf') && (get_option('forum_in_portal',true)=='1')) $url=build_url(array('page'=>'forums','url'=>$url),get_module_zone('forums'));
		return $url;
	}

	/**
	 * Get a URL to a forum.
	 *
	 * @param  integer		The ID of the forum
	 * @param  boolean		Whether it is okay to return the result using Tempcode (more efficient)
	 * @return mixed			The URL
	 */
	function forum_link($id,$tempcode_okay=false)
	{
		$url=$this->_forum_link($id,$tempcode_okay);
		if ((get_forum_type()!='none') && (get_forum_type()!='ocf') && (get_option('forum_in_portal',true)=='1'))
			$url=build_url(array('page'=>'forums','url'=>$url),get_module_zone('forums'));
		return $url;
	}

	/**
	 * Get a member's username.
	 *
	 * @param  MEMBER			The member
	 * @return ?SHORT_TEXT	The username (NULL: deleted member)
	 */
	function get_username($id)
	{
		if ($id==$this->get_guest_id())
		{
			require_code('lang');
			if (!function_exists('do_lang')) return 'Guest';
			$ret=do_lang('GUEST',NULL,NULL,NULL,NULL,false);
			if ($ret===NULL) $ret='Guest';
			return $ret;
		}

		global $USER_NAME_CACHE;
		if (isset($USER_NAME_CACHE[$id])) return $USER_NAME_CACHE[$id];
	
		$ret=$this->_get_username($id);
		if ($ret=='') $ret=NULL; // Odd, but sometimes
		$USER_NAME_CACHE[$id]=$ret;
		return $ret;
	}
	
	/**
	 * Get a member's e-mail address.
	 *
	 * @param  MEMBER			The member
	 * @return SHORT_TEXT	The e-mail address (blank: not known)
	 */
	function get_member_email_address($id)
	{
		global $MEMBER_EMAIL_CACHE;
		if (array_key_exists($id,$MEMBER_EMAIL_CACHE)) return $MEMBER_EMAIL_CACHE[$id];
	
		$ret=$this->_get_member_email_address($id);
		$MEMBER_EMAIL_CACHE[$id]=$ret;
		return $ret;
	}
	
	/**
	 * Find whether a member is staff.
	 *
	 * @param  MEMBER			The member
	 * @return boolean		The answer
	 */
	function is_staff($id)
	{
		if (is_guest($id)) return false;
	
		global $IS_STAFF_CACHE;
		if (array_key_exists($id,$IS_STAFF_CACHE)) return $IS_STAFF_CACHE[$id];

		if (($this->connection->connection_write!=$GLOBALS['SITE_DB']->connection_write) && (get_option('is_on_staff_filter',true)==='1') && (get_forum_type()!='none') && (!$GLOBALS['FORUM_DRIVER']->disable_staff_filter()))
		{
			if (strpos(strtolower(get_ocp_cpf('sites',$id)),strtolower(substr(get_site_name(),0,200)))===false)
			{
				$IS_STAFF_CACHE[$id]=false;
				return false;
			}
		}
	
		$ret=$this->_is_staff($id);
		$IS_STAFF_CACHE[$id]=$ret;
		return $ret;
	}
	
	/**
	 * If we can't get a list of admins via a usergroup query, we have to disable the staff filter - else the staff filtering can cause disaster at the point of being turned on (because it can't automatically sync).
	 *
	 * @return boolean		Whether the staff filter is disabled
	 */
	function disable_staff_filter()
	{
		if (method_exists($this,'_disable_staff_filter')) return $this->_disable_staff_filter();

		return false;
	}

	/**
	 * Find whether a member is a super administrator.
	 *
	 * @param  MEMBER			The member
	 * @return boolean		The answer
	 */
	function is_super_admin($id)
	{
		if (is_guest($id)) return false;

		global $IS_SUPER_ADMIN_CACHE;
		if (isset($IS_SUPER_ADMIN_CACHE[$id])) return $IS_SUPER_ADMIN_CACHE[$id];
	
		$ret=$this->_is_super_admin($id);
		$IS_SUPER_ADMIN_CACHE[$id]=$ret;
		return $ret;
	}
	
	/**
	 * Get a list of the super admin usergroups.
	 *
	 * @return array			The list of usergroups
	 */
	function get_super_admin_groups()
	{
		global $ADMIN_GROUP_CACHE;
		if ($ADMIN_GROUP_CACHE!==NULL) return $ADMIN_GROUP_CACHE;

		$ret=$this->_get_super_admin_groups();
		$ADMIN_GROUP_CACHE=$ret;
		return $ret;
	}
	
	/**
	 * Get a list of the moderator usergroups.
	 *
	 * @return array			The list of usergroups
	 */
	function get_moderator_groups()
	{
		global $MODERATOR_GROUP_CACHE,$IN_MINIKERNEL_VERSION;
		if ((!is_null($MODERATOR_GROUP_CACHE)) && (($IN_MINIKERNEL_VERSION==0) || ($MODERATOR_GROUP_CACHE!=array()))) return $MODERATOR_GROUP_CACHE;
	
		$ret=$this->_get_moderator_groups();
		$MODERATOR_GROUP_CACHE=$ret;
		return $ret;
	}
	
	/**
	 * Get a map of forum usergroups (id=>name).
	 *
	 * @param  boolean		Whether to obscure the name of hidden usergroups
	 * @param  boolean		Whether to only grab permissive usergroups
	 * @param  boolean		Do not limit things even if there are huge numbers of usergroups
	 * @param  ?array			Usergroups that must be included in the results (NULL: no extras must be)
	 * @param  ?MEMBER		Always return usergroups of this member (NULL: current member)
	 * @param  boolean		Whether to completely skip hidden usergroups
	 * @return array			The map
	 */
	function get_usergroup_list($hide_hidden=false,$only_permissive=false,$force_show_all=false,$force_find=NULL,$for_member=NULL,$skip_hidden=false)
	{
		global $USERGROUP_LIST_CACHE;
		if ((!is_null($USERGROUP_LIST_CACHE)) && (isset($USERGROUP_LIST_CACHE[$hide_hidden][$only_permissive][$force_show_all][serialize($force_find)][$for_member][$skip_hidden])))
		{
			return $USERGROUP_LIST_CACHE[$hide_hidden][$only_permissive][$force_show_all][serialize($force_find)][$for_member][$skip_hidden];
		}
	
		$ret=$this->_get_usergroup_list($hide_hidden,$only_permissive,$force_show_all,$force_find,$for_member,$skip_hidden);
		if (count($ret)!=0) // Conditional is for when installing... can't cache at point of there being no usergroups
		{
			if (is_null($USERGROUP_LIST_CACHE)) $USERGROUP_LIST_CACHE=array();
			$USERGROUP_LIST_CACHE[$hide_hidden][$only_permissive][$force_show_all][serialize($force_find)][$for_member][$skip_hidden]=$ret;
		}
		return $ret;
	}
	
	/**
	 * Get a list of usergroups a member is in.
	 *
	 * @param  MEMBER			The member
	 * @param  boolean		Whether to skip looking at secret usergroups.
	 * @param  boolean		Whether to take probation into account
	 * @return array			The list of usergroups
	 */
	function get_members_groups($id,$skip_secret=false,$handle_probation=true)
	{
		if ((is_guest($id)) && (get_forum_type()=='ocf'))
		{
			static $ret=NULL;
			if ($ret===NULL) $ret=array(db_get_first_id());
			return $ret;
		}
	
		global $USERS_GROUPS_CACHE;
		if (isset($USERS_GROUPS_CACHE[$id][$skip_secret][$handle_probation])) return $USERS_GROUPS_CACHE[$id][$skip_secret][$handle_probation];

		$ret=$this->_get_members_groups($id,$skip_secret,$handle_probation);
		$USERS_GROUPS_CACHE[$id][$skip_secret][$handle_probation]=$ret;
		return $ret;
	}
	
	/**
	 * Get the current member's theme identifier.
	 *
	 * @param  ?ID_TEXT		The zone we are getting the theme for (NULL: current zone)
	 * @return ID_TEXT		The theme identifier
	 */
	function get_theme($zone_for=NULL)
	{
		global $SITE_INFO;

		if ($zone_for!==NULL)
		{
			$zone_theme=$GLOBALS['SITE_DB']->query_value('zones','zone_theme',array('zone_name'=>$zone_for));
			if ($zone_theme!='-1')
			{
				if ((!isset($SITE_INFO['no_disk_sanity_checks'])) || ($SITE_INFO['no_disk_sanity_checks']=='0'))
				{
					if (!is_dir(get_custom_file_base().'/themes/'.$zone_theme)) return $this->get_theme();
				}

				return $zone_theme;
			}
			return $this->get_theme();
		}

		global $CACHED_THEME;
		if ($CACHED_THEME!==NULL) return $CACHED_THEME;

		global $IN_MINIKERNEL_VERSION;
		if (($IN_MINIKERNEL_VERSION==1) || (in_safe_mode())) return 'default';

		// Try hardcoded in URL
		$CACHED_THEME=filter_naughty(get_param('keep_theme',get_param('utheme','-1')));
		if ($CACHED_THEME!='-1')
		{
			if ((!is_dir(get_file_base().'/themes/'.$CACHED_THEME)) && (!is_dir(get_custom_file_base().'/themes/'.$CACHED_THEME)))
			{
				$theme=$CACHED_THEME;
				$CACHED_THEME='default';
				require_code('site');
				attach_message(do_lang_tempcode('NO_SUCH_THEME',escape_html($theme)),'warn');
				$CACHED_THEME=NULL;
			} else
			{
				if (($CACHED_THEME=='default') || (has_category_access(get_member(),'theme',$CACHED_THEME)))
				{
					return $CACHED_THEME;
				} else
				{
					$theme=$CACHED_THEME;
					$CACHED_THEME='default';
					attach_message(do_lang_tempcode('NO_THEME_PERMISSION',escape_html($theme)),'warn');
					$CACHED_THEME=NULL;
				}
			}
		} else $CACHED_THEME=NULL;

		// Try hardcoded in ocPortal
		global $ZONE;
		$zone_theme=($ZONE===NULL)?$GLOBALS['SITE_DB']->query_value_null_ok('zones','zone_theme',array('zone_name'=>get_zone_name())):$ZONE['zone_theme'];
		$default_theme=((get_page_name()=='login') && (get_option('root_zone_login_theme')=='1'))?$GLOBALS['SITE_DB']->query_value('zones','zone_theme',array('zone_name'=>'')):$zone_theme;
		if (($default_theme!==NULL) && ($default_theme!='-1'))
		{
			if ((!isset($SITE_INFO['no_disk_sanity_checks'])) || ($SITE_INFO['no_disk_sanity_checks']=='0'))
			{
				if (!is_dir(get_custom_file_base().'/themes/'.$default_theme)) $default_theme='-1';
			}
		}
		if ($default_theme!='-1')
		{
			$CACHED_THEME=$default_theme;
			if ($CACHED_THEME=='') $CACHED_THEME='default';
			return $CACHED_THEME;
		}
		if ($default_theme=='-1') $default_theme='default';

		// Get from forums
		$CACHED_THEME=filter_naughty($this->_get_theme());
		if (($CACHED_THEME=='') || (($CACHED_THEME!='default') && (!is_dir(get_custom_file_base().'/themes/'.$CACHED_THEME)))) $CACHED_THEME='default';
		if ($CACHED_THEME=='-1') $CACHED_THEME='default';
		require_code('permissions');
		if (($CACHED_THEME!='default') && (!has_category_access(get_member(),'theme',$CACHED_THEME)))
		{
			$CACHED_THEME='default';
		}

		if ($CACHED_THEME=='') $CACHED_THEME='default';

		return $CACHED_THEME;
	}
	
	/**
	 * Get the number of new forum posts on the system in the last 24 hours.
	 *
	 * @return integer			Number of forum posts
	 */
	function get_num_new_forum_posts()
	{
		$value=NULL;//get_value_newer_than('num_new_forum_posts',time()-60*60*6);
		if (is_null($value))
		{
			$value=strval($this->_get_num_new_forum_posts());
			//set_value('num_new_forum_posts',$value);
		}
		return intval($value);
	}

}


