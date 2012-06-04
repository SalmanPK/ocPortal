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
 * @package		ocf_forum
 */

class Hook_sw_ocf_forum
{

	/**
	 * Standard modular run function for features in the setup wizard.
	 *
	 * @param  array		Default values for the fields, from the install-profile.
	 * @return tempcode	An input field.
	 */
	function get_fields($field_defaults)
	{
		if (get_forum_type()!='ocf') return new ocp_tempcode();

		require_lang('ocf');
		$fields=new ocp_tempcode();

		$dbs_back=$GLOBALS['NO_DB_SCOPE_CHECK'];
		$GLOBALS['NO_DB_SCOPE_CHECK']=true;

		$test=$GLOBALS['SITE_DB']->query_value_null_ok('f_groups','id',array('id'=>db_get_first_id()+7));
		if (!is_null($test))
			$fields->attach(form_input_tick(do_lang_tempcode('HAVE_DEFAULT_RANK_SET'),do_lang_tempcode('DESCRIPTION_HAVE_DEFAULT_RANK_SET'),'have_default_rank_set',array_key_exists('have_default_rank_set',$field_defaults)?($field_defaults['have_default_rank_set']=='1'):false));

		$test=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'f_emoticons WHERE e_code<>\':P\' AND e_code<>\';)\' AND e_code<>\':)\' AND e_code<>\':)\' AND e_code<>\':\\\'(\'');
		if (count($test)!=0)
			$fields->attach(form_input_tick(do_lang_tempcode('HAVE_DEFAULT_FULL_EMOTICON_SET'),do_lang_tempcode('DESCRIPTION_HAVE_DEFAULT_FULL_EMOTICON_SET'),'have_default_full_emoticon_set',array_key_exists('have_default_full_emoticon_set',$field_defaults)?($field_defaults['have_default_full_emoticon_set']=='1'):true));

		$fields_l=array('im_aim','im_msn','im_jabber','im_yahoo','im_skype','interests','location','occupation','sn_google','sn_facebook','sn_twitter');
		foreach ($fields_l as $field)
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('f_custom_fields f LEFT JOIN '.get_table_prefix().'translate t ON t.id=f.cf_name','f.id',array('text_original'=>do_lang('DEFAULT_CPF_'.$field.'_NAME')));
			if (!is_null($test))
			{
				$fields->attach(form_input_tick(do_lang_tempcode('HAVE_DEFAULT_CPF_SET'),do_lang_tempcode('DESCRIPTION_HAVE_DEFAULT_CPF_SET'),'have_default_cpf_set',array_key_exists('have_default_cpf_set',$field_defaults)?($field_defaults['have_default_cpf_set']=='1'):false));
				break;
			}
		}

		$GLOBALS['NO_DB_SCOPE_CHECK']=$dbs_back;

		return $fields;
	}

	/**
	 * Standard modular run function for setting features from the setup wizard.
	 */
	function set_fields()
	{
		if (get_forum_type()!='ocf') return;

		$dbs_back=$GLOBALS['NO_DB_SCOPE_CHECK'];
		$GLOBALS['NO_DB_SCOPE_CHECK']=true;

		require_lang('ocf');
		if (post_param_integer('have_default_rank_set',0)==0)
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('f_groups','id',array('id'=>db_get_first_id()+8));
			if (!is_null($test))
			{
				$promotion_target=ocf_get_group_property(db_get_first_id()+8,'promotion_target');
				if (!is_null($promotion_target))
				{
					$GLOBALS['SITE_DB']->query_update('f_groups',array('g_promotion_target'=>NULL,'g_promotion_threshold'=>NULL,'g_rank_image'=>''),array('id'=>db_get_first_id()+8),'',1);
					for ($i=db_get_first_id()+4;$i<db_get_first_id()+8;$i++)
					{
						require_code('ocf_groups_action');
						require_code('ocf_groups_action2');
						ocf_delete_group($i);
					}
				}
				$_name=ocf_get_group_property(db_get_first_id()+8,'name');
				if (is_integer($_name))
					lang_remap($_name,do_lang('MEMBER'));
			}
		}
		if (post_param_integer('have_default_full_emoticon_set',0)==0)
		{
			$GLOBALS['SITE_DB']->query('DELETE FROM '.get_table_prefix().'f_emoticons WHERE e_code<>\':P\' AND e_code<>\';)\' AND e_code<>\':)\' AND e_code<>\':)\' AND e_code<>\':\\\'(\'');
		}
		if (post_param_integer('have_default_cpf_set',0)==0)
		{
			$fields=array('im_aim','im_msn','im_yahoo','im_skype','interests','location','occupation');
			foreach ($fields as $field)
			{
				$test=$GLOBALS['SITE_DB']->query_value_null_ok('f_custom_fields f LEFT JOIN '.get_table_prefix().'translate t ON t.id=f.cf_name','f.id',array('text_original'=>do_lang('DEFAULT_CPF_'.$field.'_NAME')));
				if (!is_null($test))
				{
					require_code('ocf_members_action');
					require_code('ocf_members_action2');
					ocf_delete_custom_field($test);
				}
			}
		}

		$GLOBALS['NO_DB_SCOPE_CHECK']=$dbs_back;
	}

	/**
	 * Standard modular run function for blocks in the setup wizard.
	 *
	 * @return array		Map of block names, to display types.
	 */
	function get_blocks()
	{
		if (get_forum_type()=='ocf')
		{
			return array(array(),array('side_ocf_personal_topics'=>array('PANEL_NONE','PANEL_NONE')));
		}
		return array(array(),array());
	}

}


