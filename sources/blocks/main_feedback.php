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
 * @package		core_feedback_features
 */

class Block_main_feedback
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
		$info['version']=1;
		$info['locked']=false;
		$info['parameters']=array('param','forum');
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		require_code('feedback');
		require_javascript('javascript_validation');

		$is_occle_talking=((ocp_srv('HTTP_USER_AGENT')=='ocPortal') && (ocp_srv('HTTP_HOST')=='ocportal.com'));

		$self_url=get_self_url();
		$self_title=get_page_name();
	
		$type='block_main_feedback';
		$id=array_key_exists('param',$map)?$map['param']:'';
	
		$out=new ocp_tempcode();
		if (post_param_integer('_comment_form_post',0)==1)
		{
			if (!has_no_forum())
			{
				$hidden=actualise_post_comment(true,$type,$id,$self_url,$self_title,array_key_exists('forum',$map)?$map['forum']:NULL,($is_occle_talking) || (get_option('captcha_on_feedback')=='0'),1,false,true,true);
				if (array_key_exists('title',$_POST))
				{
					$redirect=get_param('redirect',NULL);
					if (!is_null($redirect))
					{
						$redirect_screen=redirect_screen(get_page_title('_FEEDBACK'),$redirect,do_lang_tempcode('FEEDBACK_THANKYOU'));

						@ob_end_clean();
						$echo=globalise($redirect_screen,NULL,'',true);
						$echo->evaluate_echo();
						exit();
					} else attach_message(do_lang_tempcode('SUCCESS'),'inform');
				}
			} else
			{
				$post=post_param('post','');
				$title=post_param('title','');
				if ($post!='')
				{
					require_code('notifications');
					dispatch_notification(
						'new_feedback',
						$type,
						do_lang('NEW_FEEDBACK_SUBJECT',$title,NULL,NULL,get_site_default_lang()),
						do_lang('NEW_FEEDBACK_MESSAGE',$post,NULL,NULL,get_site_default_lang())
					);

					$email_from=trim(post_param('email',$GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member())));
					if ($email_from!='')
					{
						require_code('mail');
						mail_wrap(do_lang('YOUR_MESSAGE_WAS_SENT_SUBJECT',$title),do_lang('YOUR_MESSAGE_WAS_SENT_BODY',$post),array($email_from),NULL,'','',3,NULL,false,get_member());
					}
				}
			}
		}

		// Comment posts
		$forum=get_option('comments_forum_name');
		$count=0;
		$_comments=$GLOBALS['FORUM_DRIVER']->get_forum_topic_posts($GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier($forum,$type.'_'.$id),$count);
		if ($_comments!==-1)
		{
			$em=$GLOBALS['FORUM_DRIVER']->get_emoticon_chooser();
			require_javascript('javascript_editing');
			$comcode_help=build_url(array('page'=>'userguide_comcode'),get_comcode_zone('userguide_comcode',false));
			require_javascript('javascript_validation');
			$comment_url=get_self_url();

			if (addon_installed('captcha'))
			{
				require_code('captcha');
				$use_captcha=((!$is_occle_talking) && (get_option('captcha_on_feedback')=='1') && (use_captcha()));
				if ($use_captcha)
				{
					generate_captcha();
				}
			} else $use_captcha=false;
			$comment_details=do_template('COMMENTS_POSTING_FORM',array('_GUID'=>'4ca32620f3eb68d9cc820b18265792d7','JOIN_BITS'=>'','FIRST_POST_URL'=>'','FIRST_POST'=>'','USE_CAPTCHA'=>$use_captcha,'POST_WARNING'=>get_param('post_warning',''),'COMMENT_TEXT'=>'','GET_EMAIL'=>false,'EMAIL_OPTIONAL'=>true,'GET_TITLE'=>true,'EM'=>$em,'DISPLAY'=>'block','COMMENT_URL'=>$comment_url,'TITLE'=>do_lang_tempcode('FEEDBACK')));
		} else $comment_details=new ocp_tempcode();

		$out->attach($comment_details);
		return $out;
	}

}


