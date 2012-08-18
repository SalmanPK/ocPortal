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

class Hook_worldpay
{

	// Requires:
	//  callback URL set in control panel ('CMS') to be set to "http://<WPDISPLAY ITEM=MC_callback>"
	//  digest password sent as 'secret' to WorldPay tech support
	//  account must be set as 'live' in control panel ('CMS') once testing is done
	//  WorldPay-side payment pages will probably want customing
	//  Logos, refund policies, and contact details [e-mail, phone, postal], may need coding into the templates
	//  FuturePay must be enabled for subscriptions to work (contact WorldPay about it)

	/**
	 * Get the gateway username.
	 *
	 * @return string			The answer.
	 */
	function _get_username()
	{
		return ecommerce_test_mode()?get_option('ipn_test'):get_option('ipn');
	}

	/**
	 * Get the IPN URL.
	 *
	 * @return URLPATH		The IPN url.
	 */
	function get_ipn_url()
	{
		return 'https://select.worldpay.com/wcc/purchase';
	}

	/**
	 * Get the card/gateway logos and other gateway-required details.
	 *
	 * @return tempcode  	The stuff.
	 */
	function get_logos()
	{
		$inst_id=ecommerce_test_mode()?get_option('ipn_test'):get_option('ipn');
		$address=str_replace(chr(10),'<br />',escape_html(get_option('pd_address')));
		$email=get_option('pd_email');
		$number=get_option('pd_number');
		return do_template('ECOM_LOGOS_WORLDPAY',array('_GUID'=>'4b3254b330b3b1719d66d2b754c7a8c8','INST_ID'=>$inst_id,'PD_ADDRESS'=>$address,'PD_EMAIL'=>$email,'PD_NUMBER'=>$number));
	}

	/**
	 * Generate a transaction ID.
	 *
	 * @return string	A transaction ID.
	 */
	function generate_trans_id()
	{
		return md5(uniqid(strval(mt_rand(0,1000)),true));
	}

	/**
	 * Make a transaction (payment) button.
	 *
	 * @param  ID_TEXT		The product codename.
	 * @param  SHORT_TEXT	The human-readable product title.
	 * @param  AUTO_LINK		The purchase ID.
	 * @param  float			A transaction amount.
	 * @param  ID_TEXT		The currency to use.
	 * @return tempcode		The button
	 */
	function make_transaction_button($product,$item_name,$purchase_id,$amount,$currency)
	{
		$username=$this->_get_username();
		$ipn_url=$this->get_ipn_url();
		$email_address=$GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member());
		$trans_id=$this->generate_trans_id();
		$digest=md5(get_option('ipn_digest').':'.$trans_id.':'.float_to_raw_string($amount).':'.$currency);
		$GLOBALS['SITE_DB']->query_insert('trans_expecting',array(
			'id'=>$trans_id,
			'e_purchase_id'=>$purchase_id,
			'e_item_name'=>$item_name,
			'e_member_id'=>get_member(),
			'e_amount'=>float_to_raw_string($amount),
			'e_ip_address'=>get_ip_address(),
			'e_session_id'=>get_session_id(),
			'e_time'=>time(),
			'e_length'=>NULL,
			'e_length_units'=>'',
		));
		return do_template('ECOM_BUTTON_VIA_WORLDPAY',array('_GUID'=>'56c78a4e16c0e7f36fcfbe57d37bc3d3','PRODUCT'=>$product,'ITEM_NAME'=>$item_name,'DIGEST'=>$digest,'TEST_MODE'=>ecommerce_test_mode(),'PURCHASE_ID'=>strval($trans_id),'AMOUNT'=>float_to_raw_string($amount),'CURRENCY'=>$currency,'USERNAME'=>$username,'IPN_URL'=>$ipn_url,'EMAIL_ADDRESS'=>$email_address));
	}

	/**
	 * Make a subscription (payment) button.
	 *
	 * @param  ID_TEXT		The product codename.
	 * @param  SHORT_TEXT	The human-readable product title.
	 * @param  AUTO_LINK		The purchase ID.
	 * @param  float			A transaction amount.
	 * @param  integer		The subscription length in the units.
	 * @param  ID_TEXT		The length units.
	 * @set    d w m y
	 * @param  ID_TEXT		The currency to use.
	 * @return tempcode		The button
	 */
	function make_subscription_button($product,$item_name,$purchase_id,$amount,$length,$length_units,$currency)
	{
		$username=$this->_get_username();
		$ipn_url=$this->get_ipn_url();
		$trans_id=$this->generate_trans_id();
		$length_units_2='1';
		$first_repeat=time();
		switch ($length_units)
		{
			case 'd':
				$length_units_2='1';
				$first_repeat=60*60*24*$length;
				break;
			case 'w':
				$length_units_2='2';
				$first_repeat=60*60*24*7*$length;
				break;
			case 'm':
				$length_units_2='3';
				$first_repeat=60*60*24*31*$length;
				break;
			case 'y':
				$length_units_2='4';
				$first_repeat=60*60*24*365*$length;
				break;
		}
		$digest=md5(get_option('ipn_digest').':'.$trans_id.':'.float_to_raw_string($amount).':'.$currency.$length_units_2.strval($length));
		$GLOBALS['SITE_DB']->query_insert('trans_expecting',array(
			'id'=>$trans_id,
			'e_purchase_id'=>$purchase_id,
			'e_item_name'=>$item_name,
			'e_member_id'=>get_member(),
			'e_amount'=>float_to_raw_string($amount),
			'e_ip_address'=>get_ip_address(),
			'e_session_id'=>get_session_id(),
			'e_time'=>time(),
			'e_length'=>NULL,
			'e_length_units'=>'',
		));
		return do_template('ECOM_SUBSCRIPTION_BUTTON_VIA_WORLDPAY',array('_GUID'=>'1f88716137762a467edbf5fbb980c6fe','PRODUCT'=>$product,'DIGEST'=>$digest,'TEST'=>ecommerce_test_mode(),'LENGTH'=>strval($length),'LENGTH_UNITS_2'=>$length_units_2,'ITEM_NAME'=>$item_name,'PURCHASE_ID'=>strval($trans_id),'AMOUNT'=>float_to_raw_string($amount),'FIRST_REPEAT'=>date('Y-m-d',$first_repeat),'CURRENCY'=>$currency,'USERNAME'=>$username,'IPN_URL'=>$ipn_url));
	}

	/**
	 * Make a subscription cancellation button.
	 *
	 * @param  ID_TEXT	The purchase ID.
	 * @return tempcode	The button
	 */
	function make_cancel_button($purchase_id)
	{
		$cancel_url=build_url(array('page'=>'subscriptions','type'=>'cancel','id'=>$purchase_id),get_module_zone('subscriptions'));
		return do_template('ECOM_CANCEL_BUTTON_VIA_WORLDPAY',array('_GUID'=>'187fba57424e7850b9e21fc147de48eb','CANCEL_URL'=>$cancel_url,'PURCHASE_ID'=>$purchase_id));
	}

	/**
	 * Find whether the hook auto-cancels (if it does, auto cancel the given trans-id).
	 *
	 * @param  string		Transaction ID to cancel
	 * @return ?boolean	True: yes. False: no. (NULL: cancels via a user-URL-directioning)
	 */
	function auto_cancel($trans_id)
	{
		return false;
	}

	/**
	 * Find a transaction fee from a transaction amount. Regular fees aren't taken into account.
	 *
	 * @param  float	A transaction amount.
	 * @return float	The fee
	 */
	function get_transaction_fee($amount)
	{
		return 0.045*$amount; // for credit card. Debit card is a flat 50p
	}

	/**
	 * Handle IPN's. The function may produce output, which would be returned to the Payment Gateway. The function may do transaction verification.
	 *
	 * @return array	A long tuple of collected data.
	 */
	function handle_transaction()
	{
		//$myfile=fopen(get_file_base().'/data_custom/ecommerce.log','wt');
		//fwrite($myfile,serialize($_POST)."\n".serialize($_GET));
		//fclose($myfile);
		//$_POST=unserialize('a:36:{s:8:"testMode";s:3:"100";s:8:"authCost";s:4:"15.0";s:8:"currency";s:3:"GBP";s:7:"address";s:1:"a";s:13:"countryString";s:11:"South Korea";s:10:"callbackPW";s:10:"s35645dxr4";s:12:"installation";s:5:"84259";s:3:"fax";s:1:"a";s:12:"countryMatch";s:1:"B";s:7:"transId";s:9:"222873126";s:3:"AVS";s:4:"0000";s:12:"amountString";s:11:"&#163;15.00";s:8:"postcode";s:1:"a";s:7:"msgType";s:10:"authResult";s:4:"name";s:1:"a";s:3:"tel";s:1:"a";s:11:"transStatus";s:1:"Y";s:4:"desc";s:15:"Property Advert";s:8:"cardType";s:10:"Mastercard";s:4:"lang";s:2:"en";s:9:"transTime";s:13:"1171243476007";s:16:"authAmountString";s:11:"&#163;15.00";s:10:"authAmount";s:4:"15.0";s:9:"ipAddress";s:12:"84.9.162.135";s:4:"cost";s:4:"15.0";s:6:"instId";s:5:"84259";s:6:"amount";s:4:"15.0";s:8:"compName";s:32:"The Accessible Property Register";s:7:"country";s:2:"KR";s:11:"MC_callback";s:63:"www.kivi.co.uk/ClientFiles/APR/data/ecommerce.php?from=worldpay";s:14:"rawAuthMessage";s:22:"cardbe.msg.testSuccess";s:5:"email";s:16:"vaivak@gmail.com";s:12:"authCurrency";s:3:"GBP";s:11:"rawAuthCode";s:1:"A";s:6:"cartId";s:32:"3ecd645f632f0304067fb565e71b4dcd";s:8:"authMode";s:1:"A";}');
		//$_GET=unserialize('a:3:{s:4:"from";s:8:"worldpay";s:7:"msgType";s:10:"authResult";s:12:"installation";s:5:"84259";}');

		$txn_id=post_param('transId');
		$cart_id=post_param('cartId');
		if (post_param('futurePayType','')=='regular')
		{
			$subscription=true;
		} else
		{
			$subscription=false;
		}

		$transaction_rows=$GLOBALS['SITE_DB']->query_select('trans_expecting',array('*'),array('id'=>$cart_id),'',1);
		if (!array_key_exists(0,$transaction_rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$transaction_row=$transaction_rows[0];

		$member_id=$transaction_row['e_member_id'];
		$item_name=$subscription?'':$transaction_row['e_item_name'];
		$purchase_id=$transaction_row['e_purchase_id'];

		$code=post_param('transStatus');
		$success=($code=='Y');
		$message=post_param('rawAuthMessage');

		$payment_status=$success?'Completed':'Failed';
		$reason_code='';
		$pending_reason='';
		$memo='';
		$mc_gross=post_param('authAmount');
		$mc_currency=post_param('authCurrency');
		$email=$GLOBALS['FORUM_DRIVER']->get_member_email_address($member_id);

		if (post_param('callbackPW')!=get_option('callback_password')) my_exit(do_lang('IPN_UNVERIFIED'));

		if ($success)
		{
			require_code('notifications');
			dispatch_notification('payment_received',NULL,do_lang('PAYMENT_RECEIVED_SUBJECT',$txn_id,NULL,NULL,get_lang($member_id)),do_lang('PAYMENT_RECEIVED_BODY',float_format(floatval($mc_gross)),$mc_currency,get_site_name(),get_lang($member_id)),array($member_id),A_FROM_SYSTEM_PRIVILEGED);
		}

		if ($success) $_url=build_url(array('page'=>'purchase','type'=>'finish','product'=>get_param('product',NULL),'message'=>'<WPDISPLAY ITEM=banner>'),get_module_zone('purchase'));
		else $_url=build_url(array('page'=>'purchase','type'=>'finish','cancel'=>1,'message'=>do_lang_tempcode('DECLINED_MESSAGE',$message)),get_module_zone('purchase'));
		$url=$_url->evaluate();

		echo http_download_file($url);

		if(addon_installed('shopping'))
		{
			$this->store_shipping_address($purchase_id);
		}

		return array($purchase_id,$item_name,$payment_status,$reason_code,$pending_reason,$memo,$mc_gross,$mc_currency,$txn_id,'');
	}

	/**
	 * Store shipping address for orders
	 *
	 * @param  AUTO_LINK		Order id
	 * @return ?mixed			Address id (NULL: No address record found)
	 */
	function store_shipping_address($order_id)
	{
		if (is_null(post_param('first_name',NULL))) return NULL;

		if (is_null($GLOBALS['SITE_DB']->query_select_value_if_there('shopping_order_addresses','id',array('order_id'=>$order_id))))
		{
			$shipping_address=array();
			$shipping_address['order_id']=$order_id;
			$shipping_address['address_name']=post_param('delvName','');
			$shipping_address['address_street']=post_param('delvAddress','');
			$shipping_address['address_zip']=post_param('delvPostcode','');
			$shipping_address['address_city']=post_param('city','');
			$shipping_address['address_country']=	post_param('delvCountryString','');
			$shipping_address['receiver_email']=post_param('email','');

			return $GLOBALS['SITE_DB']->query_insert('shopping_order_addresses',$shipping_address,true);	
		}

		return NULL;
	}

}


