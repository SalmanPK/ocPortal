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
 * @package		pointstore
 */

class Hook_pointstore_topic_pin
{

	/**
	 * Standard pointstore item initialisation function.
	 */
	function init()
	{
		require_lang('ocf');
	}

	/**
	 * Standard pointstore item initialisation function.
	 *
	 * @return array			The "shop fronts"
	 */
	function info()
	{
		$class=str_replace('hook_pointstore_','',strtolower(get_class($this)));

		if (get_option('is_on_'.$class.'_buy')=='0') return array();

		$next_url=build_url(array('page'=>'_SELF','type'=>'action','id'=>$class),'_SELF');
		return array(do_template('POINTSTORE_'.strtoupper($class),array('NEXT_URL'=>$next_url)));
	}

	/**
	 * Standard interface stage of pointstore item purchase.
	 *
	 * @return tempcode		The UI
	 */
	function action()
	{
		$class=str_replace('hook_pointstore_','',strtolower(get_class($this)));

		if (get_option('is_on_'.$class.'_buy')=='0') return new ocp_tempcode();

		$title=get_screen_title('TOPIC_PINNING');

		$cost=intval(get_option($class));
		$next_url=build_url(array('page'=>'_SELF','type'=>'action_done','id'=>$class),'_SELF');
		$points_left=available_points(get_member());

		// Check points
		if (($points_left<$cost) && (!has_privilege(get_member(),'give_points_self')))
		{
			return warn_screen($title,do_lang_tempcode('_CANT_AFFORD',integer_format($cost),integer_format($points_left)));
		}

		require_code('form_templates');
		$fields=new ocp_tempcode();
		if (get_forum_type()=='ocf')
		{
			$set_name='topic';
			$required=true;
			$set_title=do_lang_tempcode('FORUM_TOPIC');
			$field_set=alternate_fields_set__start($set_name);

			$field_set->attach(form_input_tree_list(do_lang_tempcode('CHOOSE'),'','select_topic_id',NULL,'choose_forum_topic',array(),false));

			$field_set->attach(form_input_integer(do_lang_tempcode('IDENTIFIER'),do_lang_tempcode('DESCRIPTION_FORUM_TOPIC_ID'),'manual_topic_id',NULL,false));

			$fields->attach(alternate_fields_set__end($set_name,$set_title,'',$field_set,$required));
		} else
		{
			$fields->attach(form_input_integer(do_lang_tempcode('FORUM_TOPIC'),do_lang_tempcode('ENTER_TOPIC_ID_MANUALLY'),'manual_topic_id',NULL,false));
		}

		$text=do_lang_tempcode('PIN_TOPIC_A',integer_format($cost),integer_format($points_left-$cost));

		return do_template('FORM_SCREEN',array('_GUID'=>'8cabf882d5cbe4d354cc6efbcf92ebf9','TITLE'=>$title,'TEXT'=>$text,'URL'=>$next_url,'FIELDS'=>$fields,'HIDDEN'=>'','SUBMIT_NAME'=>do_lang_tempcode('PURCHASE')));
	}

	/**
	 * Standard actualisation stage of pointstore item purchase.
	 *
	 * @return tempcode		The UI
	 */
	function action_done()
	{
		$class=str_replace('hook_pointstore_','',strtolower(get_class($this)));

		if (get_option('is_on_'.$class.'_buy')=='0') return new ocp_tempcode();

		$topic_id=post_param_integer('select_topic_id',-1);
		if ($topic_id==-1)
		{
			$_topic_id=post_param('manual_topic_id');
			$topic_id=intval($_topic_id);
		}

		$title=get_screen_title('TOPIC_PINNING');

		// Check points
		$cost=intval(get_option($class));
		$points_left=available_points(get_member());
		if (($points_left<$cost) && (!has_privilege(get_member(),'give_points_self')))
		{
			return warn_screen($title,do_lang_tempcode('_CANT_AFFORD',integer_format($cost),integer_format($points_left)));
		}

		// Actuate
		$GLOBALS['FORUM_DRIVER']->pin_topic($topic_id);
		require_code('points2');
		charge_member(get_member(),$cost,do_lang('TOPIC_PINNING'));
		$GLOBALS['SITE_DB']->query_insert('sales',array('date_and_time'=>time(),'memberid'=>get_member(),'purchasetype'=>'TOPIC_PINNING','details'=>strval($topic_id),'details2'=>''));

		// Show message
		$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('ORDER_GENERAL_DONE'));
	}

}


