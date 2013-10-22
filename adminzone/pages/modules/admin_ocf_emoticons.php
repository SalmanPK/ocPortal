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
 * @package		core_ocf
 */

require_code('crud_module');

/**
 * Module page class.
 */
class Module_admin_ocf_emoticons extends standard_crud_module
{
	var $lang_type='EMOTICON';
	var $select_name='EMOTICON';
	var $orderer='e_code';
	var $array_key='e_code';
	var $title_is_multi_lang=false;
	var $non_integer_id=true;
	var $possibly_some_kind_of_upload=true;
	var $do_preview=NULL;
	var $menu_label='EMOTICONS';

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array_merge(array('misc'=>'EMOTICONS'),parent::get_entry_points());
	}

	var $title;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @param  boolean		Whether this is running at the top level, prior to having sub-objects called.
	 * @param  ?ID_TEXT		The screen type to consider for meta-data purposes (NULL: read from environment).
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run($top_level=true,$type=NULL)
	{
		$type=get_param('type','misc');

		require_lang('ocf');

		set_helper_panel_pic('pagepics/emoticons');
		set_helper_panel_tutorial('tut_emoticons');

		if ($type=='import')
		{
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('EMOTICONS')),array('_SELF:_SELF:import',do_lang_tempcode('CHOOSE'))));
		}

		if ($type=='_import')
		{
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('EMOTICONS')),array('_SELF:_SELF:import',do_lang_tempcode('CHOOSE')),array('_SELF:_SELF:import',do_lang_tempcode('IMPORT_EMOTICONS'))));
		}

		if ($type=='import' && $type=='_import')
		{
			$this->title=get_screen_title('IMPORT_EMOTICONS');
		}

		return parent::pre_run($top_level);
	}

	/**
	 * Standard crud_module run_start.
	 *
	 * @param  ID_TEXT		The type of module execution
	 * @return tempcode		The output of the run
	 */
	function run_start($type)
	{
		$this->add_one_label=do_lang_tempcode('ADD_EMOTICON');
		$this->edit_this_label=do_lang_tempcode('EDIT_THIS_EMOTICON');
		$this->edit_one_label=do_lang_tempcode('EDIT_EMOTICON');

		require_lang('dearchive');
		require_code('images');

		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();

		require_code('ocf_general_action');
		require_code('ocf_general_action2');

		if ($type=='ad')
		{
			require_javascript('javascript_ajax');
			$script=find_script('snippet');
			$this->javascript="
				var form=document.getElementById('main_form');
				form.old_submit=form.onsubmit;
				form.onsubmit=function()
					{
						document.getElementById('submit_button').disabled=true;
						var url='".addslashes($script)."?snippet=exists_emoticon&name='+window.encodeURIComponent(form.elements['code'].value);
						if (!do_ajax_field_test(url))
						{
							document.getElementById('submit_button').disabled=false;
							return false;
						}
						document.getElementById('submit_button').disabled=false;
						if (typeof form.old_submit!='undefined' && form.old_submit) return form.old_submit();
						return true;
					};
			";
		}

		if ($type=='misc') return $this->misc();
		if ($type=='import') return $this->import();
		if ($type=='_import') return $this->_import();
		return new ocp_tempcode();
	}

	/**
	 * The do-next manager for before content management.
	 *
	 * @return tempcode		The UI
	 */
	function misc()
	{
		require_code('templates_donext');
		return do_next_manager(get_screen_title('EMOTICONS'),comcode_lang_string('DOC_EMOTICONS'),
			array(
				/*	 type							  page	 params													 zone	  */
				array('emoticons',array('_SELF',array('type'=>'import'),'_SELF'),do_lang('IMPORT_EMOTICONS')),
				array('add_one',array('_SELF',array('type'=>'ad'),'_SELF'),do_lang('ADD_EMOTICON')),
				array('edit_one',array('_SELF',array('type'=>'ed'),'_SELF'),do_lang('EDIT_EMOTICON')),
			),
			do_lang('EMOTICONS')
		);
	}

	/**
	 * The UI to import in bulk from an archive file.
	 *
	 * @return tempcode		The UI
	 */
	function import()
	{
		if ($GLOBALS['SITE_DB']->connection_write!=$GLOBALS['SITE_DB']->connection_write)
		{
			attach_message(do_lang_tempcode('EDITING_ON_WRONG_MSN'),'warn');
		}

		require_code('form_templates');

		$post_url=build_url(array('page'=>'_SELF','type'=>'_import','uploading'=>1),'_SELF');
		$fields=new ocp_tempcode();
		$supported='tar';
		if ((function_exists('zip_open')) || (get_option('unzip_cmd')!='')) $supported.=', zip';
		$fields->attach(form_input_upload_multi(do_lang_tempcode('UPLOAD'),do_lang_tempcode('DESCRIPTION_ARCHIVE_IMAGES',escape_html($supported),escape_html(str_replace(',',', ',get_option('valid_images')))),'file',true,NULL,NULL,true,str_replace(' ','',get_option('valid_images').','.$supported)));

		$text=paragraph(do_lang_tempcode('IMPORT_EMOTICONS_WARNING'));
		require_code('images');
		$max=floatval(get_max_image_size())/floatval(1024*1024);
		/*if ($max<1.0)	Ok - this is silly! Emoticons are tiny.
		{
			require_code('files2');
			$config_url=get_upload_limit_config_url();
			$text->attach(paragraph(do_lang_tempcode(is_null($config_url)?'MAXIMUM_UPLOAD':'MAXIMUM_UPLOAD_STAFF',escape_html(($max>10.0)?integer_format(intval($max)):float_format($max)),escape_html(is_null($config_url)?'':$config_url))));
		}*/

		$hidden=build_keep_post_fields();
		$hidden->attach(form_input_hidden('test','1'));
		handle_max_file_size($hidden);

		return do_template('FORM_SCREEN',array('_GUID'=>'1910e01ec183392f6b254671dc7050a3','TITLE'=>$this->title,'FIELDS'=>$fields,'SUBMIT_NAME'=>do_lang_tempcode('BATCH_IMPORT_ARCHIVE_CONTENTS'),'URL'=>$post_url,'TEXT'=>$text,'HIDDEN'=>$hidden));
	}

	/**
	 * The actualiser to import in bulk from an archive file.
	 *
	 * @return tempcode		The UI
	 */
	function _import()
	{
		post_param('test'); // To pick up on max file size exceeded errors

		require_code('uploads');
		require_code('images');
		is_swf_upload(true);

		foreach ($_FILES as $attach_name=>$__file)
		{
			$tmp_name=$__file['tmp_name'];
			$file=$__file['name'];
			switch (get_file_extension($file))
			{
				case 'zip':
					if ((!function_exists('zip_open')) && (get_option('unzip_cmd')=='')) warn_exit(do_lang_tempcode('ZIP_NOT_ENABLED'));
					if (!function_exists('zip_open'))
					{
						require_code('m_zip');
						$mzip=true;
					} else $mzip=false;
					$myfile=zip_open($tmp_name);
					if (!is_integer($myfile))
					{
						while (false!==($entry=zip_read($myfile)))
						{
							// Load in file
							zip_entry_open($myfile,$entry);

							$_file=zip_entry_name($entry);

							if (is_image($_file))
							{
								if (file_exists(get_file_base().'/themes/default/images/emoticons/index.html'))
								{
									$path=get_custom_file_base().'/themes/default/images_custom/emoticons__'.basename($_file);
								} else
								{
									$path=get_custom_file_base().'/themes/default/images_custom/ocf_emoticons__'.basename($_file);
								}
								if (!file_exists(dirname($path)))
								{
									require_code('files2');
									make_missing_directory(dirname($path));
								}
								$outfile=@fopen($path,'wb') OR intelligent_write_error($path);

								$more=mixed();
								do
								{
									$more=zip_entry_read($entry);
									if (fwrite($outfile,$more)<strlen($more)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
								}
								while (($more!==false) && ($more!=''));

								fclose($outfile);
								fix_permissions($path);
								sync_file($path);

								$this->_import_emoticon($path);
							}

							zip_entry_close($entry);
						}

						zip_close($myfile);
					} else
					{
						require_code('failure');
						warn_exit(zip_error($myfile,$mzip));
					}
					break;
				case 'tar':
					require_code('tar');
					$myfile=tar_open($tmp_name,'rb');
					if ($myfile!==false)
					{
						$directory=tar_get_directory($myfile);
						foreach ($directory as $entry)
						{
							// Load in file
							$_file=$entry['path'];

							if (is_image($_file))
							{
								if (file_exists(get_file_base().'/themes/default/images/emoticons/index.html'))
								{
									$path=get_custom_file_base().'/themes/default/images_custom/emoticons__'.basename($_file);
								} else
								{
									$path=get_custom_file_base().'/themes/default/images_custom/ocf_emoticons__'.basename($_file);
								}

								$_in=tar_get_file($myfile,$entry['path'],false,$path);

								$this->_import_emoticon($path);
							}
						}

						tar_close($myfile);
					}
					break;
				default:
					if (is_image($file))
					{
						$urls=get_url('',$attach_name,'themes/default/images_custom');
						$path=$urls[0];
						$this->_import_emoticon($path);
					} else
					{
						attach_message(do_lang_tempcode('BAD_ARCHIVE_FORMAT'),'warn');
					}
			}
		}

		log_it('IMPORT_EMOTICONS');

		return $this->do_next_manager($this->title,do_lang_tempcode('SUCCESS'),NULL);
	}

	/**
	 * Import an emoticon.
	 *
	 * @param  PATH			Path to the emoticon file, on disk (must be in theme images folder).
	 */
	function _import_emoticon($path)
	{
		$emoticon_code=basename($path,'.'.get_file_extension($path));

		if (file_exists(get_file_base().'/themes/default/images/emoticons/index.html'))
		{
			$image_code='emoticons/'.$emoticon_code;
		} else
		{
			$image_code='ocf_emoticons/'.$emoticon_code;
		}
		$url_path='themes/default/images_custom/'.rawurlencode(basename($path));

		$GLOBALS['SITE_DB']->query_delete('theme_images',array('id'=>$image_code));
		$GLOBALS['SITE_DB']->query_insert('theme_images',array('id'=>$image_code,'theme'=>'default','path'=>$url_path,'lang'=>get_site_default_lang()));
		$GLOBALS['FORUM_DB']->query_delete('f_emoticons',array('e_code'=>':'.$emoticon_code.':'),'',1);
		$GLOBALS['FORUM_DB']->query_insert('f_emoticons',array(
			'e_code'=>':'.$emoticon_code.':',
			'e_theme_img_code'=>$image_code,
			'e_relevance_level'=>2,
			'e_use_topics'=>0,
			'e_is_special'=>0
		));

		persistent_cache_delete('THEME_IMAGES');
	}

	/**
	 * Get tempcode for a post template adding/editing form.
	 *
	 * @param  SHORT_TEXT	The emoticon code
	 * @param  SHORT_TEXT	The theme image code
	 * @param  integer		The relevance level of the emoticon
	 * @range  0 4
	 * @param  BINARY			Whether the emoticon is usable as a topic emoticon
	 * @param  BINARY			Whether this may only be used by privileged members
	 * @return array			A pair: The input fields, Hidden fields
	 */
	function get_form_fields($code=':-]',$theme_img_code='',$relevance_level=1,$use_topics=1,$is_special=0)
	{
		if ($GLOBALS['SITE_DB']->connection_write!=$GLOBALS['SITE_DB']->connection_write)
		{
			attach_message(do_lang_tempcode('EDITING_ON_WRONG_MSN'),'warn');
		}

		$fields=new ocp_tempcode();
		$hidden=new ocp_tempcode();

		$fields->attach(form_input_line(do_lang_tempcode('CODE'),do_lang_tempcode('DESCRIPTION_EMOTICON_CODE'),'code',$code,true));

		require_code('themes2');
		$ids=get_all_image_ids_type('ocf_emoticons',false,$GLOBALS['FORUM_DB']);

		if (get_base_url()==get_forum_base_url())
		{
			$set_name='image';
			$required=true;
			$set_title=do_lang_tempcode('IMAGE');
			$field_set=(count($ids)==0)?new ocp_tempcode():alternate_fields_set__start($set_name);

			$field_set->attach(form_input_upload(do_lang_tempcode('UPLOAD'),'','file',$required,NULL,NULL,true,str_replace(' ','',get_option('valid_images'))));

			$image_chooser_field=form_input_theme_image(do_lang_tempcode('STOCK'),'','theme_img_code',$ids,NULL,$theme_img_code,NULL,false,$GLOBALS['FORUM_DB']);
			$field_set->attach($image_chooser_field);

			$fields->attach(alternate_fields_set__end($set_name,$set_title,'',$field_set,$required));

			handle_max_file_size($hidden,'image');
		} else
		{
			if (count($ids)==0) warn_exit(do_lang_tempcode('NO_SELECTABLE_THEME_IMAGES_MSN','ocf_emoticons'));

			$image_chooser_field=form_input_theme_image(do_lang_tempcode('STOCK'),'','theme_img_code',$ids,NULL,$theme_img_code,NULL,true,$GLOBALS['FORUM_DB']);
			$fields->attach($image_chooser_field);
		}

		$list=new ocp_tempcode();
		for ($i=0;$i<=4;$i++)
		{
			$list->attach(form_input_list_entry(strval($i),$i==$relevance_level,do_lang_tempcode('EMOTICON_RELEVANCE_LEVEL_'.strval($i))));
		}
		$fields->attach(form_input_list(do_lang_tempcode('RELEVANCE_LEVEL'),do_lang_tempcode('DESCRIPTION_RELEVANCE_LEVEL'),'relevance_level',$list));

		$fields->attach(form_input_tick(do_lang_tempcode('USE_TOPICS'),do_lang_tempcode('DESCRIPTION_USE_TOPICS'),'use_topics',$use_topics==1));
		$fields->attach(form_input_tick(do_lang_tempcode('EMOTICON_IS_SPECIAL'),do_lang_tempcode('DESCRIPTION_EMOTICON_IS_SPECIAL'),'is_special',$is_special==1));

		return array($fields,$hidden);
	}

	/**
	 * Standard crud_module list function.
	 *
	 * @return tempcode		The selection list
	 */
	function nice_get_radio_entries()
	{
		$_m=$GLOBALS['FORUM_DB']->query_select('f_emoticons',array('e_code','e_theme_img_code'));
		$entries=new ocp_tempcode();
		$first=true;
		foreach ($_m as $m)
		{
			$url=find_theme_image($m['e_theme_img_code']);
			$entries->attach(do_template('FORM_SCREEN_INPUT_THEME_IMAGE_ENTRY',array('_GUID'=>'f7f64637d1c4984881f7acc68c2fe6c7','PRETTY'=>$m['e_code'],'CHECKED'=>$first,'NAME'=>'id','CODE'=>$m['e_code'],'URL'=>$url)));
			$first=false;
		}

		return $entries;
	}

	/**
	 * Standard crud_module edit form filler.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return array			A pair: The input fields, Hidden fields
	 */
	function fill_in_edit_form($id)
	{
		$m=$GLOBALS['FORUM_DB']->query_select('f_emoticons',array('*'),array('e_code'=>$id),'',1);
		if (!array_key_exists(0,$m)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$r=$m[0];

		$ret=$this->get_form_fields($r['e_code'],$r['e_theme_img_code'],$r['e_relevance_level'],$r['e_use_topics'],$r['e_is_special']);

		return $ret;
	}

	/**
	 * Standard crud_module add actualiser.
	 *
	 * @return ID_TEXT		The entry added
	 */
	function add_actualisation()
	{
		require_code('themes2');

		$theme_img_code=get_theme_img_code('ocf_emoticons',true,'file','theme_img_code',$GLOBALS['FORUM_DB']);

		ocf_make_emoticon(post_param('code'),$theme_img_code,post_param_integer('relevance_level'),post_param_integer('use_topics',0),post_param_integer('is_special',0));
		return post_param('code');
	}

	/**
	 * Standard crud_module edit actualiser.
	 *
	 * @param  ID_TEXT		The entry being edited
	 */
	function edit_actualisation($id)
	{
		require_code('themes2');

		$theme_img_code=get_theme_img_code('ocf_emoticons',true,'file','theme_img_code',$GLOBALS['FORUM_DB']);

		ocf_edit_emoticon($id,post_param('code'),$theme_img_code,post_param_integer('relevance_level'),post_param_integer('use_topics',0),post_param_integer('is_special',0));

		$this->new_id=post_param('code');
	}

	/**
	 * Standard crud_module delete actualiser.
	 *
	 * @param  ID_TEXT		The entry being deleted
	 */
	function delete_actualisation($id)
	{
		ocf_delete_emoticon($id);
	}
}


