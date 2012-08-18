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
 * @package		ocf_member_avatars
 */

class Hook_Profiles_Tabs_Edit_avatar
{

	/**
	 * Find whether this hook is active.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return boolean		Whether this hook is active
	 */
	function is_active($member_id_of,$member_id_viewing)
	{
		return (($member_id_of==$member_id_viewing) || (has_privilege($member_id_viewing,'assume_any_member')) || (has_privilege($member_id_viewing,'member_maintenance')));
	}

	/**
	 * Standard modular render function for profile tabs edit hooks.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @param  boolean		Whether to leave the tab contents NULL, if tis hook supports it, so that AJAX can load it later
	 * @return ?array			A tuple: The tab title, the tab body text (may be blank), the tab fields, extra Javascript (may be blank) the suggested tab order, hidden fields (optional) (NULL: if $leave_to_ajax_if_possible was set)
	 */
	function render_tab($member_id_of,$member_id_viewing,$leave_to_ajax_if_possible=false)
	{
		$title=do_lang_tempcode('AVATAR');

		$order=20;

		// Actualiser
		if (post_param_integer('submitting_avatar_tab',0)==1)
		{
			require_code('uploads');
			if (has_privilege($member_id_viewing,'own_avatars'))
			{
				if (((!array_key_exists('avatar_file',$_FILES)) || (!is_uploaded_file($_FILES['avatar_file']['tmp_name']))) && (!is_swf_upload())) // No upload -> URL or stock or none
				{
					$urls=array();
					$stock=post_param('avatar_alt_url','');
					if ($stock=='') // No URL -> Stock or none
					{
						$stock=post_param('avatar_stock',NULL);
						if (!is_null($stock)) // Stock
						{
							$urls[0]=($stock=='')?'':find_theme_image($stock,false,true);
						} else $urls[0]=''; // None
					} else
					{
						if ((url_is_local($stock)) && (!$GLOBALS['FORUM_DRIVER']->is_super_admin($member_id_viewing)))
						{
							$old=$GLOBALS['FORUM_DB']->query_select_value('f_members','m_avatar_url',array('id'=>$member_id_of));
							if ($old!=$stock) access_denied('ASSOCIATE_EXISTING_FILE');
						}
						$urls[0]=$stock; // URL
					}
				} else // Upload
				{
					// We have chosen an upload. Note that we will not be looking at alt_url at this point, even though it is specified below for canonical reasons
					$urls=get_url('avatar_alt_url','avatar_file',file_exists(get_custom_file_base().'/uploads/avatars')?'uploads/avatars':'uploads/ocf_avatars',0,OCP_UPLOAD_IMAGE,false);
					if (((get_base_url()!=get_forum_base_url()) || ((array_key_exists('on_msn',$GLOBALS['SITE_INFO'])) && ($GLOBALS['SITE_INFO']['on_msn']=='1'))) && ($urls[0]!='') && (url_is_local($urls[0]))) $urls[0]=get_custom_base_url().'/'.$urls[0];
				}

				$avatar_url=$urls[0];
			} else
			{
				$stock=post_param('avatar_stock');
				$avatar_url=($stock=='')?'':find_theme_image($stock,false,true);
			}

			require_code('ocf_members_action');
			require_code('ocf_members_action2');
			ocf_member_choose_avatar($avatar_url,$member_id_of);

			attach_message(do_lang_tempcode('SUCCESS_SAVE'),'inform');
		}

		if ($leave_to_ajax_if_possible) return NULL;

		// UI fields

		$avatar_url=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of,'m_avatar_url');

		require_javascript('javascript_multi');

		$fields=new ocp_tempcode();
		require_code('form_templates');
		require_code('themes2');
		$ids=get_all_image_ids_type('ocf_default_avatars',true);

		$found_it=false;
		foreach ($ids as $id)
		{
			$pos=strpos($avatar_url,'/'.$id);
			$selected=($pos!==false);
			if ($selected) $found_it=true;
		}
		$hidden=new ocp_tempcode();
		if (has_privilege($member_id_viewing,'own_avatars'))
		{
			$set_name='avatar';
			$required=false;
			$set_title=do_lang_tempcode('IMAGE');
			$field_set=alternate_fields_set__start($set_name);

			$field_set->attach(form_input_upload(do_lang_tempcode('UPLOAD'),'','avatar_file',false,NULL,NULL,true,str_replace(' ','',get_option('valid_images'))));

			$field_set->attach(form_input_line(do_lang_tempcode('URL'),'','avatar_alt_url',$found_it?'':$avatar_url,false));

			$field_set->attach(form_input_theme_image(do_lang_tempcode('STOCK'),'','avatar_stock',$ids,$avatar_url,NULL,NULL,false));

			$fields->attach(alternate_fields_set__end($set_name,$set_title,'',$field_set,$required));

			handle_max_file_size($hidden,'image');
		} else
		{
			$fields->attach(form_input_theme_image(do_lang_tempcode('STOCK'),'','avatar_stock',$ids,$avatar_url,NULL,NULL,true));
		}

		if ($avatar_url!='')
		{
			if (url_is_local($avatar_url)) $avatar_url=get_complex_base_url($avatar_url).'/'.$avatar_url;
			$avatar=do_template('OCF_TOPIC_POST_AVATAR',array('_GUID'=>'50a5902f3ab7e384d9cf99577b222cc8','AVATAR'=>$avatar_url));
		} else
		{
			$avatar=do_lang_tempcode('NONE_EM');
		}

		$width=ocf_get_member_best_group_property($member_id_of,'max_avatar_width');
		$height=ocf_get_member_best_group_property($member_id_of,'max_avatar_height');

		$text=do_template('OCF_EDIT_AVATAR_TAB',array('_GUID'=>'dbdac6ca3bc752b54d2a24a4c6e69c7c','MEMBER_ID'=>strval($member_id_of),'USERNAME'=>$GLOBALS['FORUM_DRIVER']->get_username($member_id_of),'AVATAR'=>$avatar,'WIDTH'=>integer_format($width),'HEIGHT'=>integer_format($height)));

		$hidden=new ocp_tempcode();
		$hidden->attach(form_input_hidden('submitting_avatar_tab','1'));

		$javascript='';

		return array($title,$fields,$text,$javascript,$order,$hidden);
	}

}


