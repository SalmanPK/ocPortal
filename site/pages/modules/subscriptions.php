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
 * @package		ecommerce
 */

/**
 * Module page class.
 */
class Module_subscriptions
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
		$info['version']=4;
		$info['locked']=false;
		$info['update_require_upgrade']=1;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('subscriptions');

		$dbs_bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
		$GLOBALS['NO_DB_SCOPE_CHECK']=true;
		$GLOBALS['SITE_DB']->drop_if_exists('f_usergroup_subs');
		$GLOBALS['NO_DB_SCOPE_CHECK']=$dbs_bak;
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		$dbs_bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
		$GLOBALS['NO_DB_SCOPE_CHECK']=true;

		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('subscriptions',array(
				'id'=>'*AUTO', // linked to IPN with this
				's_type_code'=>'ID_TEXT',
				's_member_id'=>'USER',
				's_state'=>'ID_TEXT', // new|pending|active|cancelled (pending means payment has been requested)
				's_amount'=>'SHORT_TEXT', // can't always find this from s_type_code
				's_special'=>'SHORT_TEXT', // depending on s_type_code, would trigger something special such as a key upgrade
				's_time'=>'TIME',
				's_auto_fund_source'=>'ID_TEXT', // Used by PayPal for nothing much, but is of real use if we need to schedule our own subscription transactions
				's_auto_fund_key'=>'SHORT_TEXT', // Ditto as above: we can serialize cc numbers etc into here
				's_via'=>'ID_TEXT',
			));
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<3))
		{
			$GLOBALS['SITE_DB']->create_table('f_usergroup_subs',array(
				'id'=>'*AUTO',
				's_title'=>'SHORT_TRANS',
				's_description'=>'LONG_TRANS',
				's_cost'=>'SHORT_TEXT',
				's_length'=>'INTEGER',
				's_length_units'=>'SHORT_TEXT',
				's_group_id'=>'GROUP',
				's_enabled'=>'BINARY',
				's_mail_start'=>'LONG_TRANS',
				's_mail_end'=>'LONG_TRANS',
				's_mail_uhoh'=>'LONG_TRANS',
				's_uses_primary'=>'BINARY',
			));
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<4))
		{
			$GLOBALS['SITE_DB']->add_table_field('subscriptions','s_via','ID_TEXT','paypal');
			$GLOBALS['SITE_DB']->add_table_field('f_usergroup_subs','s_uses_primary','BINARY');
		}

		$GLOBALS['NO_DB_SCOPE_CHECK']=$dbs_bak;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return ((is_guest()) || ($GLOBALS['SITE_DB']->query_value('subscriptions','COUNT(*)')==0))?array():array('misc'=>'MY_SUBSCRIPTIONS');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_lang('ecommerce');
		require_code('ecommerce');
		require_css('ecommerce');

		// Kill switch
		if ((ecommerce_test_mode()) && (!$GLOBALS['IS_ACTUALLY_ADMIN']) && (!has_specific_permission(get_member(),'access_ecommerce_in_test_mode'))) warn_exit(do_lang_tempcode('PURCHASE_DISABLED'));

		if (is_guest()) access_denied('NOT_AS_GUEST');

		$type=get_param('type','misc');

		if ($type=='misc') return $this->my();
		if ($type=='cancel') return $this->cancel();
		return new ocp_tempcode();
	}

	/**
	 * Show my subscriptions.
	 *
	 * @return tempcode	The interface.
	 */
	function my()
	{
		$title=get_page_title('MY_SUBSCRIPTIONS');

		$member_id=get_member();
		if (has_specific_permission(get_member(),'assume_any_member')) $member_id=get_param_integer('id',$member_id);

		$subscriptions=array();
		$rows=$GLOBALS['SITE_DB']->query_select('subscriptions',array('*'),array('s_member_id'=>$member_id));
		foreach ($rows as $row)
		{
			$product=$row['s_type_code'];
			$object=find_product($product);
			if (is_null($object)) continue;
			$products=$object->get_products(false,$product);

			$subscription_title=$products[$product][4];
			$time=get_timezoned_date($row['s_time'],true,false,false,true);
			$state=do_lang_tempcode('PAYMENT_STATE_'.$row['s_state']);

			$cancel_button=make_cancel_button($row['s_auto_fund_key'],$row['s_via']);
			$per=do_lang('_LENGTH_UNIT_'.$products[$product][3]['length_units'],integer_format($products[$product][3]['length']));

			$subscriptions[]=array('SUBSCRIPTION_TITLE'=>$subscription_title,'ID'=>strval($row['id']),'PER'=>$per,'AMOUNT'=>$row['s_amount'],'TIME'=>$time,'STATE'=>$state,'TYPE_CODE'=>$row['s_type_code'],'CANCEL_BUTTON'=>$cancel_button);
		}
		if (count($subscriptions)==0) inform_exit(do_lang_tempcode('NO_ENTRIES'));

		return do_template('ECOM_SUBSCRIPTIONS_SCREEN',array('_GUID'=>'e39cd1883ba7b87599314c1f8b67902d','TITLE'=>$title,'SUBSCRIPTIONS'=>$subscriptions));
	}

	/**
	 * Cancel a subscription.
	 *
	 * @return tempcode	The interface.
	 */
	function cancel()
	{
		$title=get_page_title('SUBSCRIPTION_CANCEL');

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('MY_SUBSCRIPTIONS'))));

		$id=get_param_integer('id');
		$via=$GLOBALS['SITE_DB']->query_value('subscriptions','s_via',array('id'=>$id));

		if (($via!='manual') && ($via!=''))
		{
			require_code('hooks/systems/ecommerce_via/'.filter_naughty($via));
			$hook=object_factory($via);
			if ($hook->auto_cancel($id)!==true)
			{
				require_code('notifications');
				$trans_id=$GLOBALS['SITE_DB']->query_value('transactions','id',array('purchase_id'=>strval($id)));
				$username=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
				dispatch_notification('subscription_cancelled_staff',NULL,do_lang('SUBSCRIPTION_CANCELLED_SUBJECT',NULL,NULL,NULL,get_site_default_lang()),do_lang('SUBSCRIPTION_CANCELLED_BODY',$trans_id,$username,NULL,get_site_default_lang()));
			}
		}

		$GLOBALS['SITE_DB']->query_delete('subscriptions',array('id'=>$id,'s_member_id'=>get_member()),'',1);

		$url=build_url(array('page'=>'_SELF'),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

}


