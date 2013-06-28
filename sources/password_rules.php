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

/**
 * Find if a member's account has expired, due to inactivity.
 *
 * @param  MEMBER			The member this is for
 * @return boolean		Whether it is
 */
function member_password_expired($member_id)
{
	$expiry_days=intval(get_option('password_expiry_days'));

	if ($expiry_days>0)
	{
		$last_time=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_last_visit_time');
		if ($last_time<time()-60*60*24*$expiry_days)
		{
			return true;
		}
	}

	return false;
}

/**
 * Find if a member's password is too old.
 *
 * @param  MEMBER			The member this is for
 * @return boolean		Whether it is
 */
function member_password_too_old($member_id)
{
	$change_days=intval(get_option('password_change_days'));

	if ($change_days>0)
	{
		$last_time=$GLOBALS['FORUM_DB']->query_select_value('f_password_history','MAX(p_time)',array(
			'p_member_id'=>$member_id,
		));
		if ($last_time<time()-60*60*24*$change_days)
		{
			return true;
		}
	}

	return false;
}

/**
 * Check the complexity of a password.
 *
 * @param  ID_TEXT		The username this is for
 * @param  string			New password
 * @param  boolean		Whether to return errors instead of dieing on them.
 * @return ?tempcode		Error (NULL: none).
 */
function check_password_complexity($username,$password,$return_errors=false)
{
	$_maximum_password_length=get_option('maximum_password_length',true);
	$maximum_password_length=1000;
	if (!is_null($_maximum_password_length)) $maximum_password_length=intval($_maximum_password_length);
	if (ocp_mb_strlen($password)>$maximum_password_length)
	{
		if ($return_errors) return do_lang_tempcode('PASSWORD_TOO_LONG',integer_format($maximum_password_length));
		warn_exit(do_lang_tempcode('PASSWORD_TOO_LONG',integer_format($maximum_password_length)));
	}

	$_minimum_password_length=get_option('minimum_password_length',true);
	$minimum_password_length=1;
	if (!is_null($_minimum_password_length)) $minimum_password_length=intval($_minimum_password_length);
	if (ocp_mb_strlen($password)<$minimum_password_length)
	{
		if ($return_errors) return do_lang_tempcode('PASSWORD_TOO_SHORT',integer_format($minimum_password_length));
		warn_exit(do_lang_tempcode('PASSWORD_TOO_SHORT',integer_format($minimum_password_length)));
	}

	$_minimum_password_strength=get_option('minimum_password_strength');
	if ($_minimum_password_strength!='1')
	{
		$minimum_strength=intval($_minimum_password_strength);
		require_code('password_strength');
		$strength=test_password($password,$username);
		if ($strength<$minimum_strength)
		{
			require_lang('password_rules');
			if ($return_errors) return do_lang_tempcode('PASSWORD_NOT_COMPLEX_ENOUGH');
			warn_exit(do_lang_tempcode('PASSWORD_NOT_COMPLEX_ENOUGH'));
		}
	}

	return NULL;
}

/**
 * Store (a hash of) and validate a new password.
 *
 * @param  MEMBER			The member this is for
 * @param  string			New password
 * @param  string			Hashed password
 * @param  string			Password salt
 * @param  boolean		Whether to skip enforcement checks
 * @param  ?TIME			The time this is logged to be happening at (NULL: now)
 */
function bump_password_change_date($member_id,$password,$password_salted,$salt,$skip_checks=false,$time=NULL)
{
	if (is_null($time)) $time=time();

	// Ensure does not re-use previous password
	if (!$skip_checks)
	{
		$past_passwords=$GLOBALS['FORUM_DB']->query_select('f_password_history',array('*'),array('p_member_id'=>$member_id),'ORDER BY p_time DESC',1000/*reasonable limit*/);
		foreach ($past_passwords as $past_password)
		{
			if (md5($past_password['p_salt'].md5($password))==$past_password['p_hash_salted'])
			{
				require_lang('password_rules');
				warn_exit(do_lang_tempcode('CANNOT_REUSE_PASSWORD'));
			}
		}
	}

	// Insert into log
	$GLOBALS['FORUM_DB']->query_insert('f_password_history',array(
		'p_member_id'=>$member_id,
		'p_hash_salted'=>$password_salted,
		'p_salt'=>$salt,
		'p_time'=>$time,
	));
}
