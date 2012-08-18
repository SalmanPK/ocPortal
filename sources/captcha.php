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
 * @package		captcha
 */

/**
 * Standard code module initialisation function.
 */
function init__captcha()
{
	require_lang('captcha');
}

/**
 * Outputs and stores information for a CAPTCHA.
 */
function captcha_script()
{
	$_code_needed=$GLOBALS['SITE_DB']->query_select_value_if_there('security_images','si_code',array('si_session_id'=>get_session_id()));
	if (is_null($_code_needed))
	{
		generate_captcha();
		$_code_needed=$GLOBALS['SITE_DB']->query_select_value_if_there('security_images','si_code',array('si_session_id'=>get_session_id()));

		/*$GLOBALS['HTTP_STATUS_CODE']='500';		This would actually be very slightly insecure, as it could be used to probe (binary) login state via rogue sites that check if CAPTCHAs had been generated
		if (!headers_sent())
		{
			if (function_exists('browser_matches'))
				if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 500 Internal server error');
		}

		warn_exit(do_lang_tempcode('CAPTCHA_NO_SESSION'));*/
	}
	if (strlen(strval($_code_needed))>6) // Encoded in ASCII (we did it like this to avoid breaking DB compatibility)
	{
		$__code_needed=str_pad(strval($_code_needed),12,'0',STR_PAD_LEFT);
		$code_needed='';
		for ($i=0;$i<strlen($__code_needed);$i+=2)
		{
			$code_needed.=chr(intval(substr($__code_needed,$i,2)));
		}
	} else
	{
		$code_needed=str_pad(strval($_code_needed),6,'0',STR_PAD_LEFT);
	}
	mt_srand($_code_needed); // Important: to stop averaging out of different attempts. This makes the distortion consistent for that particular code.

	@ini_set('ocproducts.xss_detect','0');

	$mode=get_param('mode','');
	if ($mode=='audio')
	{
		header('Content-Type: audio/x-wav');
		/*if (strstr(ocp_srv('HTTP_USER_AGENT'),'MSIE')!==false)	Useful for testing
			header('Content-Disposition: filename="securityvoice.wav"');
		else
			header('Content-Disposition: attachment; filename="securityvoice.wav"');*/
		$data='';
		for ($i=0;$i<strlen($code_needed);$i++)
		{
			$char=strtolower($code_needed[$i]);

			$file_path=get_file_base().'/data_custom/sounds/'.$char.'.wav';
			if (!file_exists($file_path))
				$file_path=get_file_base().'/data/sounds/'.$char.'.wav';
			$myfile=fopen($file_path,'rb');
			if ($i!=0)
			{
				fseek($myfile,44);
			} else
			{
				$data=fread($myfile,44);
			}
			$_data=fread($myfile,filesize($file_path));
			for ($j=0;$j<strlen($_data);$j++)
			{
				if (get_option('captcha_noise')=='1')
				{
					$amp_mod=mt_rand(-4,4);
					$_data[$j]=chr(min(255,max(0,ord($_data[$j])+$amp_mod)));
				}
				if (get_option('captcha_noise')=='1')
				{
					if (($j!=0) && (mt_rand(0,10)==1)) $data.=$_data[$j-1];
				}
				$data.=$_data[$j];
			}
			fclose($myfile);
		}

		@ini_set('zlib.output_compression','Off');

		// Fix up header
		$data=substr_replace($data,pack('V',strlen($data)-8),4,4);
		$data=substr_replace($data,pack('V',strlen($data)-44),40,4);
		header('Content-Length: '.strval(strlen($data)));
		echo $data;
		return;
	}

	// Write basic
	$characters=strlen($code_needed);
	$fonts=array();
	$width=20;
	for ($i=0;$i<$characters;$i++)
	{
		$font=mt_rand(4,5); // 1 is too small
		$fonts[]=$font;
		$width+=imagefontwidth($font)+2;
	}
	$height=imagefontheight($font)+20;
	$img=imagecreate($width,$height);
	$black=imagecolorallocate($img,0,0,0);
	$off_black=imagecolorallocate($img,mt_rand(1,45),mt_rand(1,45),mt_rand(1,45));
	$white=imagecolorallocate($img,255,255,255);
	$tricky_remap=array();
	$tricky_remap[$black]=array();
	$tricky_remap[$off_black]=array();
	$tricky_remap[$white]=array();
	for ($i=0;$i<=5;$i++)
	{
		$tricky_remap['!'.strval($black)][]=imagecolorallocate($img,0+mt_rand(0,15),0+mt_rand(0,15),0+mt_rand(0,15));
		$tricky_remap['!'.strval($off_black)][]=$off_black;
		$tricky_remap['!'.strval($white)][]=imagecolorallocate($img,255-mt_rand(0,145),255-mt_rand(0,145),255-mt_rand(0,145));
	}
	imagefill($img,0,0,$black);
	$x=10;
	foreach ($fonts as $i=>$font)
	{
		$y_dif=mt_rand(-15,15);
		imagestring($img,$font,$x,10+$y_dif,$code_needed[strlen($code_needed)-$i-1],$off_black);
		$x+=imagefontwidth($font)+2;
	}
	$x=10;
	foreach ($fonts as $i=>$font)
	{
		$y_dif=mt_rand(-5,5);
		imagestring($img,$font,$x,10+$y_dif,$code_needed[$i],$white);
		if (get_option('captcha_noise')=='1') imagestring($img,$font,$x+1,10+mt_rand(-1,1)+$y_dif,$code_needed[$i],$white);
		$x+=imagefontwidth($font)+2;
	}

	// Add some noise
	if (get_option('captcha_noise')=='1')
	{
		$noise_amount=0.02;//0.04;
		for ($i=0;$i<intval($width*$height*$noise_amount);$i++)
		{
			$x=mt_rand(0,$width);
			$y=mt_rand(0,$height);
			if (mt_rand(0,1)==0)
				imagesetpixel($img,$x,$y,$white);
			else imagesetpixel($img,$x,$y,$black);
		}
		for ($i=0;$i<$width;$i++)
		{
			for ($j=0;$j<$height;$j++)
			{
				imagesetpixel($img,$i,$j,$tricky_remap['!'.strval(imagecolorat($img,$i,$j))][mt_rand(0,5)]);
			}
		}
	}

	if (get_option('css_captcha')==='1')
	{
		echo '
		<!DOCTYPE html>
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title>'.do_lang('CONTACT_STAFF_TO_JOIN_IF_IMPAIRED').'</title>
		</head>
		<body>
		';
		echo '<div style="width: '.strval($width).'px; font-size: 0.001px; line-height: 0">';
		for ($j=0;$j<$height;$j++)
		{
			for ($i=0;$i<$width;$i++)
			{
				$colour=imagecolorsforindex($img,imagecolorat($img,$i,$j));
				echo '<span style="overflow: hidden; font-size: 0px; display: inline-block; background: rgb('.strval($colour['red']).','.strval($colour['green']).','.strval($colour['blue']).'); width: 1px; height: 1px"></span>';
			}
		}
		echo '</div>';
		echo '
		</body>
		</html>
		';
		exit();
	}

	header('Content-Type: image/png');

	imagepng($img);

	imagedestroy($img);
}

/**
 * Get a captcha (aka security code) form field.
 *
 * @return tempcode		The field
 */
function form_input_captcha()
{
	$tabindex=get_form_field_tabindex(NULL);

	generate_captcha();

	// Show template
	$input=do_template('FORM_SCREEN_INPUT_CAPTCHA',array('_GUID'=>'f7452af9b83db36685ae8a86f9762d30','TABINDEX'=>strval($tabindex)));
	return _form_input('captcha',do_lang_tempcode('CAPTCHA'),do_lang_tempcode('DESCRIPTION_CAPTCHA'),$input,true,false);
}

/**
 * Find whether captcha (the security image) should be used if preferred (making this call assumes it is preferred).
 *
 * @return boolean		Whether captcha is used
 */
function use_captcha()
{
	$answer=((is_guest()) && (intval(get_option('is_on_gd'))==1) && (intval(get_option('use_security_images'))==1) && (function_exists('imagetypes')));
	return $answer;
}

/**
 * Generate a CAPTCHA image.
 */
function generate_captcha()
{
	$session=get_session_id();

	// Clear out old codes
	$GLOBALS['SITE_DB']->query('DELETE FROM '.get_table_prefix().'security_images WHERE si_time<'.strval((integer)time()-60*30).' OR si_session_id='.strval((integer)$session));
	// HACKHACK Run a test to see if large numbers are supported
	$insert_map=array('si_time'=>time(),'si_session_id'=>$session);
	$GLOBALS['SITE_DB']->query_insert('security_images',$insert_map+array('si_code'=>333333333333),false,true);
	$test=$GLOBALS['SITE_DB']->query_select_value_if_there('security_images','si_code',$insert_map);

	// Create code
	$numbers_only=($test!==333333333333);
	$code=mixed();
	if ($numbers_only)
	{
		$code=mt_rand(0,999999);
	} else
	{
		$code='';
		$choices=array('3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','J','K','M','N','P','R','S','T','W','X','Y');
		for ($i=0;$i<6;$i++)
		{
			$choice=mt_rand(0,count($choices)-1);
			$code.=strval(ord($choices[$choice])); // NB: In ASCII code all the chars in $choices are 10-99 (i.e. 2 digit)
		}
	}

	// Clear out old codes
	$GLOBALS['SITE_DB']->query('DELETE FROM '.get_table_prefix().'security_images WHERE si_time<'.strval((integer)time()-60*30).' OR si_session_id='.strval((integer)$session));

	// Store code
	$si_code=mixed();
	if ($numbers_only)
	{
		$si_code=intval($code);
	} else
	{
		$si_code=floatval($code);
	}
	$GLOBALS['SITE_DB']->query_insert('security_images',$insert_map+array('si_code'=>$si_code),false,true);

	require_javascript('javascript_ajax');
}

/**
 * Calling this assumes captcha was needed. Checks that it was done correctly.
 *
 * @param  boolean		Whether to possibly regenerate upon error.
 */
function enforce_captcha($regenerate_on_error=true)
{
	if (use_captcha())
	{
		$code_entered=post_param('captcha');
		if (!check_captcha($code_entered,$regenerate_on_error))
		{
			$GLOBALS['HTTP_STATUS_CODE']='500';
			if (!headers_sent())
			{
				if (function_exists('browser_matches'))
					if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 500 Internal server error');
			}

			warn_exit(do_lang_tempcode('INVALID_SECURITY_CODE_ENTERED'));
		}
	}
}

/**
 * Checks a CAPTCHA.
 *
 * @param  string			CAPTCHA entered.
 * @param  boolean		Whether to possibly regenerate upon error.
 * @return boolean		Whether it is valid for the current session.
 */
function check_captcha($code_entered,$regenerate_on_error=true)
{
	if (use_captcha())
	{
		$_code_needed=$GLOBALS['SITE_DB']->query_select_value_if_there('security_images','si_code',array('si_session_id'=>get_session_id()));
		if (get_value('captcha_single_guess')==='1')
		{
			$GLOBALS['SITE_DB']->query_delete('security_images',array('si_session_id'=>get_session_id())); // Only allowed to check once
		}
		if (is_null($_code_needed))
		{
			if (get_value('captcha_single_guess')==='1')
			{
				generate_captcha();
			}
			$GLOBALS['HTTP_STATUS_CODE']='500';
			if (!headers_sent())
			{
				if (function_exists('browser_matches'))
					if ((!browser_matches('ie')) && (strpos(ocp_srv('SERVER_SOFTWARE'),'IIS')===false)) header('HTTP/1.0 500 Internal server error');
			}

			warn_exit(do_lang_tempcode('NO_SESSION_SECURITY_CODE'));
		}
		if (strlen(strval($_code_needed))>6) // Encoded in ASCII (we did it like this to avoid breaking DB compatibility)
		{
			$__code_needed=str_pad(strval($_code_needed),12,'0',STR_PAD_LEFT);
			$code_needed='';
			for ($i=0;$i<strlen($__code_needed);$i+=2)
			{
				$code_needed.=chr(intval(substr($__code_needed,$i,2)));
			}
		} else
		{
			$code_needed=str_pad(strval($_code_needed),6,'0',STR_PAD_LEFT);
		}
		$ret=(strtolower($code_needed)==strtolower($code_entered));
		if ($regenerate_on_error)
		{
			if (get_value('captcha_single_guess')==='1')
			{
				if (!$ret) generate_captcha();
			}
		}
		return $ret;
	}
	return true;
}

/**
 * Get code to do an AJAX check of the CAPTCHA.
 *
 * @return string			Javascript code.
 */
function captcha_ajax_check()
{
	if (!use_captcha()) return '';

	require_javascript('javascript_ajax');
	$script=find_script('snippet');
	return "
		var form=document.getElementById('main_form');
		if (!form) form=document.getElementById('posting_form');
		form.old_submit_b=form.onsubmit;
		form.onsubmit=function()
			{
				document.getElementById('submit_button').disabled=true;
				var url='".addslashes($script)."?snippet=captcha_wrong&name='+window.encodeURIComponent(form.elements['captcha'].value);
				if (!do_ajax_field_test(url))
				{
					document.getElementById('captcha').src+='&'; // Force it to reload latest captcha
					document.getElementById('submit_button').disabled=false;
					return false;
				}
				document.getElementById('submit_button').disabled=false;
				if (typeof form.old_submit_b!='undefined' && form.old_submit_b) return form.old_submit_b();
				return true;
			};
	";
}
