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
 * @package		recommend
 */

/**
 * Module page class.
 */
class Module_recommend
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=3;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'RECOMMEND_SITE');
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		delete_config_option('points_RECOMMEND_SITE');
		delete_menu_item_simple('_SEARCH:recommend:from={$SELF_URL&,0,0,0,from=<null>}');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		require_lang('recommend');

		if (is_null($upgrade_from))
		{
			add_config_option('RECOMMEND_SITE','points_RECOMMEND_SITE','integer','return addon_installed(\'points\')?\'350\':NULL;','POINTS','COUNT_POINTS_GIVEN');
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<3))
		{
			delete_menu_item_simple('_SEARCH:recommend:from={$REPLACE&,:,%3A,{$SELF_URL}}');
			delete_menu_item_simple('_SEARCH:recommend:from={$REPLACE,:,%3A,{$SELF_URL&,0,0,0,from=<null>}}');
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<3))
		{
			add_menu_item_simple('root_website',NULL,'RECOMMEND_SITE','_SEARCH:recommend:from={$SELF_URL&,0,0,0,from=<null>}');
		}
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_lang('recommend');
		require_code('recommend');

		$type=get_param('type','misc');

		//is it send a CSV address file to parse and later usage
		if (array_key_exists('upload',$_FILES) && isset($_FILES['upload']['tmp_name']) && strlen($_FILES['upload']['tmp_name'])>0)  return $this->gui2();

		//is there a parameter passed from the second choose contacts to send page
		if (array_key_exists('select_contacts_page',$_POST)) return $this->actual();

		//if reached these, then there should be set manually at least on email address !!!
		if (array_key_exists('email_address_0',$_POST) && strlen(trim($_POST['email_address_0']))==0)
			warn_exit(do_lang_tempcode('ERROR_NO_CONTACTS_SELECTED'));

		if ($type=='misc') return $this->gui();
		if ($type=='actual') return $this->actual();

		return new ocp_tempcode();
	}

	/**
	 * The UI for recommending the site.
	 *
	 * @return tempcode	The UI.
	 */
	function gui()
	{
		require_code('form_templates');

		global $EXTRA_HEAD;
		$EXTRA_HEAD->attach('<meta name="robots" content="noindex" />'); // XHTMLXHTML

		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='page_title';
		$NON_CANONICAL_PARAMS[]='subject';
		$NON_CANONICAL_PARAMS[]='s_message';
		$NON_CANONICAL_PARAMS[]='from';
		$NON_CANONICAL_PARAMS[]='title';
		$NON_CANONICAL_PARAMS[]='ocp';

		$page_title=get_param('page_title',NULL,true);

		$submit_name=(!is_null($page_title))?make_string_tempcode($page_title):do_lang_tempcode('SEND');
		$post_url=build_url(array('page'=>'_SELF','type'=>'actual'),'_SELF',NULL,true);

		$hidden=new ocp_tempcode();

		$name=post_param('name',is_guest()?'':$GLOBALS['FORUM_DRIVER']->get_username(get_member()));
		$recommender_email_address=post_param('recommender_email_address',$GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member()));

		$fields=new ocp_tempcode();
		$fields->attach(form_input_line(do_lang_tempcode('YOUR_NAME'),'','name',$name,true));
		$fields->attach(form_input_email(do_lang_tempcode('YOUR_EMAIL_ADDRESS'),'','recommender_email_address',$recommender_email_address,true));
		$already=array();
		foreach ($_POST as $key=>$email_address)
		{
			if (substr($key,0,14)!='email_address_') continue;

			if (get_magic_quotes_gpc()) $email_address=stripslashes($email_address);

			$already[]=$email_address;
		}

		if (is_guest())
		{
			$fields->attach(form_input_email(do_lang_tempcode('FRIEND_EMAIL_ADDRESS'),'','email_address_0',array_key_exists(0,$already)?$already[0]:'',true));
		} else
		{
			if (get_value('disable_csv_recommend')!=='1')
			{
				$set_name='people';
				$required=true;
				$set_title=do_lang_tempcode('TO');
				$field_set=alternate_fields_set__start($set_name);

				$email_address_field=form_input_line_multi(do_lang_tempcode('FRIEND_EMAIL_ADDRESS'),do_lang_tempcode('THEIR_ADDRESS'),'email_address_',$already,1,NULL,'email');
				$field_set->attach($email_address_field);

				//add an upload CSV contacts file field
				$_help_url=build_url(array('page'=>'recommend_help'),get_page_zone('recommend_help'));
				$help_url=$_help_url->evaluate();

				$field_set->attach(form_input_upload(do_lang_tempcode('UPLOAD'),do_lang_tempcode('DESCRIPTION_UPLOAD_CSV_FILE',escape_html($help_url)),'upload',false,NULL,NULL,false));

				$fields->attach(alternate_fields_set__end($set_name,$set_title,'',$field_set,$required));
			} else
			{
				$email_address_field=form_input_line_multi(do_lang_tempcode('FRIEND_EMAIL_ADDRESS'),do_lang_tempcode('THEIR_ADDRESS'),'email_address_',$already,1,NULL,'email');
				$fields->attach($email_address_field);
			}
		}

		if ((may_use_invites()) && (get_forum_type()=='ocf') && (!is_guest()))
		{
			$invites=get_num_invites(get_member());
			if ($invites>0)
			{
				require_lang('ocf');
				$invite=(count($_POST)==0)?true:(post_param_integer('invite',0)==1);
				$fields->attach(form_input_tick(do_lang_tempcode('USE_INVITE'),do_lang_tempcode('USE_INVITE_DESCRIPTION',$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())?do_lang('NA_EM'):integer_format($invites)),'invite',$invite));
			}
		}
		$message=post_param('message',NULL);
		$subject=get_param('subject',do_lang('RECOMMEND_MEMBER_SUBJECT',get_site_name()),true);
		if (is_null($message))
		{
			$message=get_param('s_message','',true);
			if ($message=='')
			{
				$from=get_param('from',NULL,true);
				if (!is_null($from))
				{
					$resource_title=get_param('title','',true);
					if ($resource_title=='') // Auto download it
					{
						$downloaded_at_link=http_download_file($from,3000,false);
						if (is_string($downloaded_at_link))
						{
							$matches=array();
							if (preg_match('#\s*<title[^>]*\s*>\s*(.*)\s*\s*<\s*/title\s*>#mi',$downloaded_at_link,$matches)!=0)
							{
								$resource_title=trim(str_replace('&ndash;','-',str_replace('&mdash;','-',@html_entity_decode($matches[1],ENT_QUOTES,get_charset()))));
								$resource_title=preg_replace('#^'.str_replace('#','\#',preg_quote(get_site_name())).' - #','',$resource_title);
								$resource_title=preg_replace('#\s+[^\d\s][^\d\s]?[^\d\s]?\s+'.str_replace('#','\#',preg_quote(get_site_name())).'$#i','',$resource_title);
							}
						}
					}
					if ($resource_title=='')
					{
						$resource_title=do_lang('THIS'); // Could not find at all, so say 'this'
					} else
					{
						$subject=get_param('subject',do_lang('RECOMMEND_MEMBER_SUBJECT_SPECIFIC',get_site_name(),$resource_title));
					}

					$message=do_lang('FOUND_THIS_ON',get_site_name(),comcode_escape($from),comcode_escape($resource_title));
				}
			}
			if (get_param_integer('ocp',0)==1)
			{
				$message=do_lang('RECOMMEND_OCPORTAL');
			}
		}

		$text=is_null($page_title)?do_lang_tempcode('RECOMMEND_SITE_TEXT'):new ocp_tempcode();

		if (!is_null(get_param('from',NULL,true)))
		{
			if (is_null($page_title))
			{
				$title=get_screen_title('RECOMMEND_LINK');
			} else
			{
				$title=get_screen_title($page_title,false);
			}
			$submit_name=do_lang_tempcode('SEND');
			$text=do_lang_tempcode('RECOMMEND_AUTO_TEXT',get_site_name());
			$need_message=true;
		} else
		{
			if (is_null($page_title))
			{
				$title=get_screen_title('_RECOMMEND_SITE',true,array(escape_html(get_site_name())));
			} else
			{
				$title=get_screen_title($page_title,false);
			}
			$hidden->attach(form_input_hidden('wrap_message','1'));
			$need_message=false;
		}

		handle_max_file_size($hidden);

		$fields->attach(form_input_line(do_lang_tempcode('SUBJECT'),'','subject',$subject,true));
		$fields->attach(form_input_text_comcode(do_lang_tempcode('MESSAGE'),do_lang_tempcode('RECOMMEND_SUP_MESSAGE'),'message',$message,$need_message));

		if (addon_installed('captcha'))
		{
			require_code('captcha');
			if (use_captcha())
			{
				$fields->attach(form_input_captcha());
				$text->attach(' ');
				$text->attach(do_lang_tempcode('FORM_TIME_SECURITY'));
			}
		}

		$hidden->attach(form_input_hidden('comcode__message','1'));

		$javascript=(function_exists('captcha_ajax_check')?captcha_ajax_check():'');

		return do_template('FORM_SCREEN',array('_GUID'=>'08a538ca8d78597b0417f464758a59fd','JAVASCRIPT'=>$javascript,'SKIP_VALIDATION'=>true,'TITLE'=>$title,'HIDDEN'=>$hidden,'FIELDS'=>$fields,'URL'=>$post_url,'SUBMIT_NAME'=>$submit_name,'TEXT'=>$text));
	}

	/**
	 * The UI for the second stage of recommending the site - when CSV file is posted
	 *
	 * @return tempcode	The UI.
	 */
	function gui2()
	{
		require_code('form_templates');

		$submit_name=do_lang_tempcode('PROCEED');
		$post_url=build_url(array('page'=>'_SELF','type'=>'actual'),'_SELF');

		$name=post_param('name',is_guest()?'':$GLOBALS['FORUM_DRIVER']->get_username(get_member()));
		$recommender_email_address=post_param('recommender_email_address',$GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member()));

		$fields=new ocp_tempcode();
		$hidden=new ocp_tempcode();
		$already=array();
		$email_counter=0;
		foreach ($_POST as $key=>$input_value)
		{
			//stripslashes if necessary
			if (get_magic_quotes_gpc()) $input_value=stripslashes($input_value);

			if (substr($key,0,14)=='email_address_')
			{
				$already[]=$input_value; //email address
				$email_counter++;
				$hidden->attach(form_input_hidden($key,$input_value));
			} else
			{
				//add hidden field to the form
				if ($key!='upload') $hidden->attach(form_input_hidden($key,$input_value));
			}
		}

		$hidden->attach(form_input_hidden('select_contacts_page','1'));

		$text=do_lang_tempcode('RECOMMEND_SITE_TEXT_CHOOSE_CONTACTS');

		$page_title=get_param('page_title',NULL,true);
		if (!is_null(get_param('from',NULL,true)))
		{
			if (is_null($page_title))
			{
				$title=get_screen_title('RECOMMEND_LINK');
			} else
			{
				$title=get_screen_title($page_title,false);
			}
		} else
		{
			if (is_null($page_title))
			{
				$title=get_screen_title('_RECOMMEND_SITE',true,array(escape_html(get_site_name())));
			} else
			{
				$title=get_screen_title($page_title,false);
			}
			$hidden->attach(form_input_hidden('wrap_message','1'));
		}

		$success_read=false;

		//start processing CSV file
		if ((get_value('disable_csv_recommend')!=='1') && (!is_guest()))
		{
			if (array_key_exists('upload',$_FILES)) // NB: We disabled SWFupload for this form so don't need to consider it
			{
				if (is_uploaded_file($_FILES['upload']['tmp_name']) && preg_match('#\.csv#',$_FILES['upload']['name'])!=0)
				{
					$possible_email_fields=array('E-mail','Email','E-mail address','Email address','Primary Email');
					$possible_name_fields=array('Name','Forename','First Name','Display Name','First');

					$fixed_contents=unixify_line_format(file_get_contents($_FILES['upload']['tmp_name']));
					$myfile=@fopen($_FILES['upload']['tmp_name'],'wb');
					if ($myfile!==false)
					{
						fwrite($myfile,$fixed_contents);
						fclose($myfile);
					}

					$myfile=fopen($_FILES['upload']['tmp_name'],'rb');

					$del=',';

					$csv_header_line_fields=fgetcsv($myfile,10240,$del);
					if ((count($csv_header_line_fields)==1) && (strpos($csv_header_line_fields[0],';')!==false))
					{
						$del=';';
						rewind($myfile);
						$csv_header_line_fields=fgetcsv($myfile,10240,$del);
					}

					$skip_next_process=false;

					if ((function_exists('mb_detect_encoding')) && (function_exists('mb_convert_encoding')) && (strlen(mb_detect_encoding($csv_header_line_fields[0],"ASCII,UTF-8,UTF-16,UTF16"))==0)) // Apple mail weirdness
					{
						//test string just for Apple mail detection
						$test_unicode=utf8_decode(mb_convert_encoding($csv_header_line_fields[0],"UTF-8","UTF-16"));
						if (preg_match('#\?\?ame#u',$test_unicode)!=0)
						{
							//THIS SHOULD BE APPLE MAIL
							foreach($csv_header_line_fields as $key=>$value)
							{
								$csv_header_line_fields[$key]=utf8_decode(mb_convert_encoding($csv_header_line_fields[$key],"UTF-8","UTF-16"));

								$found_email_address='';
								$found_name='';

								$first_row_exploded=explode(';',$csv_header_line_fields[0]);

								$email_index=1; //by default
								$name_index=0; //by default

								foreach ($csv_header_line_fields as $key => $value)
								{
									if (preg_match('#\?\?ame#',$value)!=0) $name_index=$key; //Windows mail
									if (preg_match('#E\-mail#',$value)!=0) $email_index=$key; //both
								}

								while (($csv_line=fgetcsv($myfile,10240,$del))!==false) // Reading a CSV record
								{
									foreach($csv_line as $key=>$value)
										$csv_line[$key]=utf8_decode(mb_convert_encoding($csv_line[$key],"UTF-8","UTF-16"));

									$found_email_address=(array_key_exists($email_index,$csv_line) && strlen($csv_line[$email_index])>0)?$csv_line[$email_index]:'';
									$found_email_address=(preg_match('#.*\@.*\..*#',$found_email_address)!=0)?preg_replace("#\"#",'',$found_email_address):'';
									$found_name=$found_email_address;

									if (strlen($found_email_address)>0)
									{
										$skip_next_process=true;
										//Add to the list what we've found
										$fields->attach(form_input_tick($found_name,$found_email_address,'use_details_'.strval($email_counter),true));
										$hidden->attach(form_input_hidden('details_email_'.strval($email_counter),$found_email_address));
										$hidden->attach(form_input_hidden('details_name_'.strval($email_counter),$found_name));
										$email_counter++;
										$success_read=true;
									}
								}
							}
						}
					}

					if (!$skip_next_process)
					{
						//there is a strange symbol that appears at start of the Windows Mail file, so we need to convert the first file line to catch these
						if ((function_exists('mb_check_encoding')) && (mb_check_encoding($csv_header_line_fields[0],'UTF-8')))
						{
							$csv_header_line_fields[0]=utf8_decode($csv_header_line_fields[0]);
						}

						//this means that we need to import from Windows mail (also for Outlook Express) export file, which is different from others csv export formats
						if (array_key_exists(0,$csv_header_line_fields) && (preg_match('#\?Name#',$csv_header_line_fields[0])!=0 || preg_match('#Name\;E\-mail\sAddress#',$csv_header_line_fields[0])!=0))
						{
							$found_email_address='';
							$found_name='';

							$first_row_exploded=explode(';',$csv_header_line_fields[0]);

							$email_index=1; //by default
							$name_index=0; //by default

							foreach ($first_row_exploded as $key => $value)
							{
								if (preg_match('#\?Name#',$value)!=0) $name_index=$key; //Windows mail
								if (preg_match('#^Name$#',$value)!=0) $name_index=$key; //Outlook Express
								if (preg_match('#E\-mail\sAddress#',$value)!=0) $email_index=$key; //both
							}

							while (($csv_line=fgetcsv($myfile,10240,$del))!==false) // Reading a CSV record
							{
								$row_exploded=array('','');
								$row_exploded=(array_key_exists(0,$csv_line) && strlen($csv_line['0'])>0)?explode(';',$csv_line[0]):array('','');

								$found_email_address=(strlen($row_exploded[$email_index])>0)?$row_exploded[$email_index]:'';
								$found_name=(strlen($row_exploded[$name_index])>0)?$row_exploded[$name_index]:'';

								if (strlen($found_email_address)>0)
								{
									//Add to the list what we've found
									$fields->attach(form_input_tick($found_name,do_lang_tempcode('RECOMMENDING_TO_LINE',escape_html($found_name),escape_html($found_email_address)),'use_details_'.strval($email_counter),true));
									$hidden->attach(form_input_hidden('details_email_'.strval($email_counter),$found_email_address));
									$hidden->attach(form_input_hidden('details_name_'.strval($email_counter),$found_name));
									$email_counter++;
									$success_read=true;
								}
							}
						} else
						{
							while (($csv_line=fgetcsv($myfile,10240,$del))!==false) // Reading a CSV record
							{
								$found_email_address='';
								$found_name='';

								foreach ($possible_email_fields as $field)
								{
									foreach ($csv_header_line_fields as $i=>$header_field)
									{
										if ((strtolower($header_field)==strtolower($field)) && (array_key_exists($i,$csv_line)) && ($csv_line[$i]!=''))
										{
											$found_email_address=$csv_line[$i];
											$success_read=true;
										}
									}
								}

								foreach ($possible_name_fields as $field)
								{
									foreach ($csv_header_line_fields as $i=>$header_field)
									{
										if ((strtolower($header_field)==strtolower($field)) && (array_key_exists($i,$csv_line)) && ($csv_line[$i]!=''))
										{
											$found_name=$csv_line[$i];
										}
									}
								}

								if (strlen($found_email_address)>0)
								{
									//Add to the list what we've found
									$fields->attach(form_input_tick($found_name,do_lang_tempcode('RECOMMENDING_TO_LINE',escape_html($found_name),escape_html($found_email_address)),'use_details_'.strval($email_counter),true));
									$hidden->attach(form_input_hidden('details_email_'.strval($email_counter),$found_email_address));
									$hidden->attach(form_input_hidden('details_name_'.strval($email_counter),$found_name));
									$email_counter++;
								}
							}
						}
					}

					fclose($myfile);
				}
			}
		}

		if (!$success_read)
			warn_exit(do_lang_tempcode('ERROR_NO_CONTACTS_SELECTED'));

		return do_template('FORM_SCREEN',array('PREVIEW'=>true,'SKIP_VALIDATION'=>true,'TITLE'=>$title,'HIDDEN'=>$hidden,'FIELDS'=>$fields,'URL'=>$post_url,'SUBMIT_NAME'=>$submit_name,'TEXT'=>$text));
	}

	/**
	 * The actualiser for recommending the site.
	 *
	 * @return tempcode	The UI.
	 */
	function actual()
	{
		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('RECOMMEND_SITE'))));

		$name=post_param('name');
		$message=post_param('message');
		$recommender_email_address=post_param('recommender_email_address');

		$invite=false;

		if (addon_installed('captcha'))
		{
			require_code('captcha');
			enforce_captcha();
		}

		require_code('type_validation');

		$email_adrs_to_send=array();
		$names_to_send=array();

		foreach ($_POST as $key=>$email_address)
		{
			if (substr($key,0,14)!='email_address_') continue;
			if ($email_address=='') continue;

			if (get_magic_quotes_gpc()) $email_address=stripslashes($email_address);

			if (!is_valid_email_address($email_address))
			{
				attach_message(do_lang_tempcode('INVALID_EMAIL_ADDRESS'),'warn');
				return $this->gui();
			} else
			{
				$email_adrs_to_send[]=$email_address;
				$names_to_send[]=$email_address;
			}

			if (is_guest()) break;
		}

		$adrbook_emails=array();
		$adrbook_names=array();
		$adrbook_use_these=array();
		foreach ($_POST as $key=>$email_address)
		{
			if (preg_match('#details_email_|details_name_|^use_details_#',$key)==0) continue;
			if (preg_match('#details_email_#',$key)!=0)
			{
				if (get_magic_quotes_gpc()) $email_address=stripslashes($email_address);

				if (is_valid_email_address($email_address))
				{
					$curr_num=intval(preg_replace('#details_email_#','',$key));
					$adrbook_emails[$curr_num]=$email_address;
				}
			}

			if (preg_match('#details_name_#',$key))
			{
				$curr_num=intval(preg_replace('#details_name_#','',$key));
				$adrbook_names[$curr_num]=$email_address;
			}

			if (preg_match('#^use_details_#',$key))
			{
				$curr_num=intval(preg_replace('#use_details_#','',$key));
				$adrbook_use_these[$curr_num]=$curr_num;
			}
		}

		//add emails from address book file
		foreach ($adrbook_use_these as $key=>$value)
		{
			$cur_email=(array_key_exists($key,$adrbook_emails) && strlen($adrbook_emails[$key])>0)?$adrbook_emails[$key]:'';
			$cur_name=(array_key_exists($key,$adrbook_names) && strlen($adrbook_names[$key])>0)?$adrbook_names[$key]:'';
			if (strlen($cur_email)>0)
			{
				$email_adrs_to_send[]=$cur_email;
				$names_to_send[]=(strlen($cur_name)>0)?$cur_name:$cur_email;
			}
		}

		if (count($email_adrs_to_send)==0)
			warn_exit(do_lang_tempcode('ERROR_NO_CONTACTS_SELECTED'));

		foreach ($email_adrs_to_send as $key=>$email_address)
		{
			if (get_magic_quotes_gpc()) $email_address=stripslashes($email_address);

			if (post_param_integer('wrap_message',0)==1)
			{
				$title=get_screen_title('_RECOMMEND_SITE',true,array(escape_html(get_site_name())));

				$referring_username=is_guest()?NULL:get_member();
				$_url=(post_param_integer('invite',0)==1)?build_url(array('page'=>'join','email_address'=>$email_address,'keep_referrer'=>$referring_username),get_module_zone('join')):build_url(array('page'=>'','keep_referrer'=>$referring_username),'');
				$url=$_url->evaluate();
				$join_url=$GLOBALS['FORUM_DRIVER']->join_url();
				$message=do_lang((post_param_integer('invite',0)==1)?'INVITE_MEMBER_MESSAGE':'RECOMMEND_MEMBER_MESSAGE',$name,$url,array(get_site_name(),$join_url)).$message;
			} else
			{
				$title=get_screen_title('RECOMMEND_LINK');
			}

			if ((may_use_invites()) && (get_forum_type()=='ocf') && (!is_guest()) && (post_param_integer('invite',0)==1))
			{
				$invites=get_num_invites(get_member());
				if ($invites>0)
				{
					send_recommendation_email($name,$email_address,$message,true,$recommender_email_address,post_param('subject',NULL),$names_to_send[$key]);

					$GLOBALS['FORUM_DB']->query_insert('f_invites',array(
						'i_inviter'=>get_member(),
						'i_email_address'=>$email_address,
						'i_time'=>time(),
						'i_taken'=>0
					));

					$invite=true;
				}
			}
			elseif ((get_option('is_on_invites')=='0') && (get_forum_type()=='ocf'))
			{
				$GLOBALS['FORUM_DB']->query_insert('f_invites',array( // Used for referral tracking
					'i_inviter'=>get_member(),
					'i_email_address'=>$email_address,
					'i_time'=>time(),
					'i_taken'=>0
				));
			}

			if (!$invite)
			{
				send_recommendation_email($name,$email_address,$message,false,$recommender_email_address,post_param('subject',NULL),$names_to_send[$key]);
			}
		}

		breadcrumb_set_self(do_lang_tempcode('DONE'));

		return inform_screen($title,do_lang_tempcode('RECOMMENDATION_MADE'));
	}

}


