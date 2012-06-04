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
 * @package		shopping
 */

class Hook_addon_registry_shopping
{

	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Online store functionality.';
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array('ecommerce'),
			'recommends'=>array(),
			'conflicts_with'=>array(),
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(

			'sources/hooks/systems/notifications/order_dispatched.php',
			'sources/hooks/systems/notifications/new_order.php',
			'sources/hooks/systems/notifications/low_stock.php',
			'sources/hooks/modules/admin_setupwizard_installprofiles/shopping.php',
			'sources/hooks/systems/config_default/allow_opting_out_of_tax.php',
			'sources/hooks/systems/config_default/shipping_cost_factor.php',
			'themes/default/images/EN/pageitem/cart_add.png',
			'themes/default/images/EN/page/cart_view.png',
			'themes/default/images/EN/page/cart_add.png',
			'themes/default/images/EN/page/cart_update.png',
			'themes/default/images/EN/page/shopping_continue.png',
			'themes/default/images/EN/page/shopping_buy_now.png',
			'themes/default/images/EN/page/cart_empty.png',
			'themes/default/images/EN/page/cart_checkout.png',
			'sources/hooks/systems/ecommerce/catalogue_items.php',
			'sources/hooks/systems/ecommerce/cart_orders.php',
			'sources/hooks/blocks/main_staff_checklist/ecommerce_orders.php',
			'sources/shopping.php',
			'site/pages/modules/shopping.php',
			'CATALOGUE_products_CATEGORY_SCREEN.tpl',
			'CATALOGUE_products_ENTRY_FIELD.tpl',
			'CATALOGUE_products_ENTRY_SCREEN.tpl',
			'CATALOGUE_products_ENTRY.tpl',
			'CATALOGUE_products_ENTRY_EMBED.tpl',
			'CATALOGUE_products_LINE.tpl',
			'CATALOGUE_products_LINE_WRAP.tpl',
			'CATEGORY_products_ENTRY.tpl',
			'RESULTS_products_TABLE.tpl',
			'JAVASCRIPT_SHOPPING.tpl',
			'SHOPPING_CART_PROCEED.tpl',
			'SHOPPING_CART_STAGE_PAY.tpl',
			'SHOPPING_CART_SCREEN.tpl',
			'SHOPPING_ITEM_QUANTITY_FIELD.tpl',
			'SHOPPING_ITEM_REMOVE_FIELD.tpl',
			'CATALOGUE_ENTRY_ADD_TO_CART.tpl',
			'adminzone/pages/modules/admin_orders.php',
			'lang/EN/shopping.ini',
			'sources/hooks/systems/addon_registry/shopping.php',
			'sources/hooks/systems/ocf_cpf_filter/shopping_cart.php',
			'shopping.css',
			'themes/default/images/bigicons/orders.png',
			'themes/default/images/bigicons/show_orders.png',
			'themes/default/images/bigicons/undispatched.png',
			'themes/default/images/results/add_note.png',
			'themes/default/images/results/delete.gif',
			'themes/default/images/results/dispatch.png',
			'themes/default/images/results/hold.png',
			'themes/default/images/results/return.gif',
			'themes/default/images/results/view.gif',
			'ADMIN_ORDER_ACTIONS.tpl',
			'CART_LOGO.tpl',
			'ECOM_ADMIN_ORDERS_DETAILS_SCREEN.tpl',
			'ECOM_ADMIN_ORDERS_SCREEN.tpl',
			'ECOM_ORDERS_DETAILS_SCREEN.tpl',
			'ECOM_ORDERS_SCREEN.tpl',
			'RESULTS_cart_TABLE.tpl',
			'RESULTS_TABLE_cart_ENTRY.tpl',
			'RESULTS_TABLE_cart_FIELD.tpl',
			'SHIPPING_ADDRESS.tpl',
			'ECOM_CART_BUTTON_VIA_PAYPAL.tpl',
			'ECOMMERCE_ITEM_DETAILS.tpl',
		);
	}


	/**
	* Get mapping between template names and the method of this class that can render a preview of them
	*
	* @return array			The mapping
	*/
	function tpl_previews()
	{
		return array(
				'ADMIN_ORDER_ACTIONS.tpl'=>'administrative__ecom_admin_orders_details_screen',
				'ECOM_ADMIN_ORDERS_SCREEN.tpl'=>'administrative__ecom_admin_orders_screen',
				'SHIPPING_ADDRESS.tpl'=>'administrative__ecom_admin_orders_details_screen',
				'ECOM_ADMIN_ORDERS_DETAILS_SCREEN.tpl'=>'administrative__ecom_admin_orders_details_screen',
				'ECOMMERCE_ITEM_DETAILS.tpl'=>'ecommerce_item_details',
				'SHOPPING_ITEM_QUANTITY_FIELD.tpl'=>'shopping_cart_screen',
				'SHOPPING_ITEM_REMOVE_FIELD.tpl'=>'shopping_cart_screen',
				'ECOM_CART_BUTTON_VIA_PAYPAL.tpl'=>'ecom_cart_button_via_paypal',
				'SHOPPING_CART_PROCEED.tpl'=>'shopping_cart_screen',
				'SHOPPING_CART_SCREEN.tpl'=>'shopping_cart_screen',
				'ECOM_ORDERS_SCREEN.tpl'=>'ecom_orders_screen',
				'ECOM_ORDERS_DETAILS_SCREEN.tpl'=>'ecom_orders_details_screen',
				'RESULTS_cart_TABLE.tpl'=>'shopping_cart_screen',
				'RESULTS_TABLE_cart_ENTRY.tpl'=>'shopping_cart_screen',
				'RESULTS_TABLE_cart_FIELD.tpl'=>'shopping_cart_screen',
				'CART_LOGO.tpl'=>'field_map_products_entry_screen',
				'CATALOGUE_products_ENTRY_FIELD.tpl'=>'field_map_products_entry_screen',
				'CATALOGUE_products_ENTRY_SCREEN.tpl'=>'field_map_products_entry_screen',
				'CATALOGUE_products_ENTRY.tpl'=>'field_map_products_entry_screen',
				'CATALOGUE_products_ENTRY_EMBED.tpl'=>'list_catalogue_products',
				'CATALOGUE_products_LINE.tpl'=>'list_catalogue_screen_products',
				'CATALOGUE_products_LINE_WRAP.tpl'=>'list_catalogue_screen_products',
				'CATALOGUE_ENTRY_ADD_TO_CART.tpl'=>'table_category_screen_products',
				'CATEGORY_products_ENTRY.tpl'=>'table_category_screen_products',
				'CATALOGUE_products_CATEGORY_SCREEN.tpl'=>'table_category_screen_products',
				'RESULTS_products_TABLE.tpl'=>'results_products_table',
				'SHOPPING_CART_STAGE_PAY.tpl'=>'shopping_cart_stage_pay',
			);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__shopping_cart_stage_pay()
	{
		return array(
			lorem_globalise(
				do_lorem_template('SHOPPING_CART_STAGE_PAY',array(
					'TRANSACTION_BUTTON'=>placeholder_button(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__ecom_admin_orders_screen()
	{
		$results_browser = placeholder_result_browser();

		$cells = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD_TITLE',array('VALUE'=>$v)));
		}
		$fields_title = $cells;

		$order_entries = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$cells = new ocp_tempcode();
			foreach (placeholder_array() as $k=>$v)
			{
				$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD',array('VALUE'=>lorem_word()),NULL,false,'RESULTS_TABLE_FIELD'));
			}
			$order_entries->attach(do_lorem_template('RESULTS_TABLE_ENTRY',array('VALUES'=>$cells),NULL,false,'RESULTS_TABLE_ENTRY'));
		}

		$selectors = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$selectors->attach(do_lorem_template('RESULTS_BROWSER_SORTER',array('SELECTED'=>'','NAME'=>lorem_word(),'VALUE'=>lorem_word())));
		}
		$sort = do_lorem_template('RESULTS_BROWSER_SORT',array('HIDDEN'=>'','SORT'=>lorem_word(),'RAND'=>placeholder_random(),'URL'=>placeholder_url(),'SELECTORS'=>$selectors));

		$results_table = do_lorem_template('RESULTS_TABLE',array('TEXT_ID'=>lorem_phrase(),'FIELDS_TITLE'=>$fields_title,'FIELDS'=>$order_entries,'MESSAGE'=>new ocp_tempcode(),'SORT'=>$sort,'BROWSER'=>'','WIDTHS'=>array(placeholder_number())),NULL,false,'RESULTS_TABLE');

		return array(
			lorem_globalise(
				do_lorem_template('ECOM_ADMIN_ORDERS_SCREEN',array(
					'TITLE'=>lorem_title(),
					'CURRENCY'=>lorem_phrase(),
					'ORDERS'=>placeholder_array(),
					'RESULTS_BROWSER'=>$results_browser,
					'RESULT_TABLE'=>$results_table,
					'SEARCH_URL'=>placeholder_url(),
					'HIDDEN'=>'',
					'SEARCH_VAL'=>lorem_phrase(),
						)
			),NULL,'',true),
		);
	}

	/**
	* Function to display custom result tables
	* 	
	* @param	 ID_TEXT			Tpl set name
	* @return tempcode		Tempcode
	*/
	function show_custom_tables($tplset)
	{
		$fields_title	=	new ocp_tempcode();
		foreach (array(lorem_word(),lorem_word_2(),lorem_word(),lorem_word_2()) as $k=>$v)
		{
			$fields_title->attach(do_lorem_template('RESULTS_TABLE_FIELD_TITLE',array('VALUE'=>$v)));
      }
		$entries = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$cells = new ocp_tempcode();

			$entry_data	=	array(lorem_word(),placeholder_date(),lorem_word(),lorem_word());

			foreach ($entry_data as $k=>$v)
			{
				$cells->attach(do_lorem_template('RESULTS_TABLE_'.$tplset.'FIELD',array('VALUE'=>$v),NULL,false,'RESULTS_TABLE_FIELD'));
			}
			$entries->attach(do_lorem_template('RESULTS_TABLE_'.$tplset.'ENTRY',array('VALUES'=>$cells),NULL,false,'RESULTS_TABLE_ENTRY'));
		}

		$selectors = new ocp_tempcode();
		foreach (placeholder_array(11) as $k=>$v)
		{
			$selectors->attach(do_lorem_template('RESULTS_BROWSER_SORTER',array('SELECTED'=>'','NAME'=>$v,'VALUE'=>$v)));
		}

		$sort = do_lorem_template('RESULTS_BROWSER_SORT',array('HIDDEN'=>'','SORT'=>lorem_word(),'RAND'=>placeholder_random(),'URL'=>placeholder_url(),'SELECTORS'=>$selectors));

		return do_lorem_template('RESULTS_'.$tplset.'TABLE',array('FIELDS_TITLE'=>$fields_title,'FIELDS'=>$entries,'MESSAGE'=>new ocp_tempcode(),'SORT'=>$sort,'BROWSER'=>placeholder_result_browser(),'WIDTHS'=>array(placeholder_number())),NULL,false,'RESULTS_TABLE');
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__ecom_admin_orders_details_screen()
	{
		$results_browser = placeholder_result_browser();

		//results_table starts
		//results_entry starts
		$cells = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD_TITLE',array('VALUE'=>$v)));
		}
		$fields_title = $cells;

		$product_entries = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$cells = new ocp_tempcode();
			foreach (placeholder_array() as $k=>$v)
			{
				$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD',array('VALUE'=>lorem_word()),NULL,false,'RESULTS_TABLE_FIELD'));
			}
			$product_entries->attach(do_lorem_template('RESULTS_TABLE_ENTRY',array('VALUES'=>$cells),NULL,false,'RESULTS_TABLE_ENTRY'));
		}
		//results_entry ends

		$selectors = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$selectors->attach(do_lorem_template('RESULTS_BROWSER_SORTER',array('SELECTED'=>'','NAME'=>lorem_word(),'VALUE'=>lorem_word())));
		}
		$sort = do_lorem_template('RESULTS_BROWSER_SORT',array('HIDDEN'=>'','SORT'=>lorem_word(),'RAND'=>placeholder_random(),'URL'=>placeholder_url(),'SELECTORS'=>$selectors));

		$results_table = do_lorem_template('RESULTS_TABLE',array('TEXT_ID'=>lorem_phrase(),'FIELDS_TITLE'=>$fields_title,'FIELDS'=>$product_entries,'MESSAGE'=>new ocp_tempcode(),'SORT'=>$sort,'BROWSER'=>'','WIDTHS'=>array(placeholder_number())),NULL,false,'RESULTS_TABLE');
		//results_table ends

		$order_actions	= do_lorem_template('ADMIN_ORDER_ACTIONS',array('ORDER_TITLE'=>lorem_phrase(),'ORDR_ACT_URL'=>placeholder_url(),'ORDER_STATUS'=>lorem_word()));

		$shipping_address =	do_lorem_template('SHIPPING_ADDRESS',array('ADDRESS_NAME'=>lorem_phrase(),'ADDRESS_STREET'=>lorem_phrase(),'ADDRESS_CITY'=>lorem_phrase(),'ADDRESS_ZIP'=>lorem_phrase(),'ADDRESS_COUNTRY'=>lorem_phrase(),'RECEIVER_EMAIL'=>lorem_phrase()));

		return array(
			lorem_globalise(
				do_lorem_template('ECOM_ADMIN_ORDERS_DETAILS_SCREEN',array(
					'TITLE'=>lorem_title(),
					'TEXT'=>lorem_sentence(),
					'CURRENCY'=>lorem_phrase(),
					'RESULT_TABLE'=>$results_table,
					'RESULTS_BROWSER'=>$results_browser,
					'ORDER_NUMBER'=>placeholder_number(),
					'ADD_DATE'=>placeholder_date(),
					'TOTAL_PRICE'=>placeholder_number(),
					'ORDERED_BY_MEMBER_ID'=>placeholder_id(),
					'ORDERED_BY_USERNAME'=>lorem_word(),
					'ORDER_STATUS'=>lorem_phrase(),
					'NOTES'=>lorem_phrase(),
					'PURCHASED_VIA'=>lorem_phrase(),
					'ORDER_ACTIONS'=>$order_actions,
					'SHIPPING_ADDRESS'=>$shipping_address,
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__ecommerce_item_details()
	{
		return array(
			lorem_globalise(
				do_lorem_template('ECOMMERCE_ITEM_DETAILS',array(
					'FIELDS'=>placeholder_fields(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__ecom_cart_button_via_paypal()
	{
		$items = array();
		foreach (placeholder_array() as $k=>$v)
		{
			$items[] = array('PRODUCT_NAME'=>lorem_word(),'PRICE'=>placeholder_number(),'QUANTITY'=>placeholder_number());
		}
		return array(
			lorem_globalise(
				do_lorem_template('ECOM_CART_BUTTON_VIA_PAYPAL',array(
					'ITEMS'=>$items,
					'CURRENCY'=>lorem_phrase(),
					'PAYMENT_ADDRESS'=>lorem_word(),
					'IPN_URL'=>placeholder_url(),
					'ORDER_ID'=>placeholder_id(),
					'NOTIFICATION_TEXT'=>lorem_sentence_html(),
					'MEMBER_ADDRESS'=>placeholder_array(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__shopping_cart_screen()
	{
		//results_table starts
		//results_entry starts


		$shopping_cart = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$cells = new ocp_tempcode();
			foreach (placeholder_array(8) as $v)
			{
				$cells->attach(do_lorem_template('RESULTS_TABLE_FIELD_TITLE',array('VALUE'=>$v)));
			}
			$fields_title = $cells;

			$product_image = placeholder_image();
			$product_link = placeholder_link();
			$currency = lorem_word();
			$edit_qnty = do_lorem_template('SHOPPING_ITEM_QUANTITY_FIELD',array('PRODUCT_ID'=>strval($k),'QUANTITY'=>lorem_phrase()));
			$del_item =	do_lorem_template('SHOPPING_ITEM_REMOVE_FIELD',array('PRODUCT_ID'=>strval($k)));

			$values = array(
								$product_image,
								$product_link,
								$edit_qnty,
								$currency.(string)placeholder_number(),
								$currency.(string)placeholder_number(),
								$currency.(string)placeholder_number(),
								$currency.placeholder_number(),
								$del_item
							);
			$cells = new ocp_tempcode();
			foreach ($values as $value)
			{
				$cells->attach(do_lorem_template('RESULTS_TABLE_cart_FIELD',array('VALUE'=>$value,'CLASS'=>''),NULL,false,'RESULTS_TABLE_FIELD'));
			}
			$shopping_cart->attach(do_lorem_template('RESULTS_TABLE_cart_ENTRY',array('VALUES'=>$cells),NULL,false,'RESULTS_TABLE_ENTRY'));
		}
		//results_entry ends

		$selectors = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$selectors->attach(do_lorem_template('RESULTS_BROWSER_SORTER',array('SELECTED'=>'','NAME'=>lorem_word(),'VALUE'=>lorem_word())));
		}
		$sort = do_lorem_template('RESULTS_BROWSER_SORT',array('HIDDEN'=>'','SORT'=>lorem_word(),'RAND'=>placeholder_random(),'URL'=>placeholder_url(),'SELECTORS'=>$selectors));

		$results_table = do_lorem_template('RESULTS_cart_TABLE',array('FIELDS_TITLE'=>$fields_title,'FIELDS'=>$shopping_cart,'MESSAGE'=>new ocp_tempcode(),'SORT'=>$sort,'BROWSER'=>lorem_word(),'WIDTHS'=>array(placeholder_number())),NULL,false,'RESULTS_TABLE');
		//results_table ends

		$proceed_box = do_lorem_template('SHOPPING_CART_PROCEED',array('SUB_TOTAL'=>float_format(floatval(placeholder_number())),'SHIPPING_COST'=>float_format(floatval(placeholder_number())),'GRAND_TOTAL'=>float_format(floatval(placeholder_number())),'CHECKOUT_URL'=>placeholder_link(),'PROCEED'=>lorem_phrase(),'CURRENCY'=>lorem_word(),'PAYMENT_FORM'=>placeholder_form()));

		return array(
			lorem_globalise(
				do_lorem_template('SHOPPING_CART_SCREEN',array(
					'TITLE'=>lorem_title(),
					'RESULT_TABLE'=>$results_table,
					'FORM_URL'=>placeholder_url(),
					'CONT_SHOPPING'=>placeholder_url(),
					'MESSAGE'=>lorem_phrase(),
					'BACK'=>placeholder_link(),
					'PRO_IDS'=>placeholder_id(),
					'EMPTY_CART'=>lorem_phrase(),
					'EMPTY'=>lorem_phrase(),
					'UPDATE'=>lorem_phrase(),
					'CONTINUE_SHOPPING'=>lorem_phrase(),
					'PROCEED_BOX'=>$proceed_box,
					'ALLOW_OPTOUT_TAX'=>lorem_phrase(),
					'ALLOW_OPTOUT_TAX_VALUE'=>lorem_phrase(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__ecom_orders_screen()
	{
		$orders = array();
		foreach (placeholder_array() as $v)
		{
			$orders[] = array(
				'ORDER_DET_URL'=>placeholder_url(),
				'ORDER_TITLE'=>lorem_word(),
				'AMOUNT'=>placeholder_id(),
				'TIME'=>placeholder_time(),
				'STATE'=>lorem_word_2(),
				'NOTE'=>lorem_phrase()
			);
		}
		return array(
			lorem_globalise(
				do_lorem_template('ECOM_ORDERS_SCREEN',array(
					'TITLE'=>lorem_title(),
					'CURRENCY'=>lorem_phrase(),
					'ORDERS'=>$orders,
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__ecom_orders_details_screen()
	{
		$orders = array();
		foreach (placeholder_array() as $v)
		{
			$orders[] = array(
				'PRODUCT_DET_URL'=>placeholder_url(),
				'PRODUCT_NAME'=>lorem_word(),
				'AMOUNT'=>placeholder_id(),
				'QUANTITY'=>"2",
				'DISPATCH_STATUS'=>lorem_word_2()
			);
		}
		return array(
			lorem_globalise(
				do_lorem_template('ECOM_ORDERS_DETAILS_SCREEN',array(
					'TITLE'=>lorem_title(),
					'CURRENCY'=>lorem_phrase(),
					'PRODUCTS'=>$orders
						)
			),NULL,'',true),
		);
	}

   /**
	* Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	* Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	* Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	*
	* @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	*/
	function tpl_preview__table_category_screen_products()
	{
		require_lang('catalogues');
		require_css('catalogues');

		$subcategories = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$subcategories->attach(do_lorem_template('CATEGORY_products_ENTRY',array('ID'=>placeholder_id(),'NAME_FIELD'=>lorem_word(),'AJAX_EDIT_URL'=>placeholder_url(),'URL'=>placeholder_url(),'REP_IMAGE'=>placeholder_image(),'CHILDREN'=>lorem_phrase(),'NAME'=>lorem_phrase(),'NAME_PLAIN'=>lorem_phrase(),'DESCRIPTION'=>lorem_phrase())));
		}

		$results_browser = placeholder_result_browser();

		$cart_link = do_lorem_template('CATALOGUE_ENTRY_ADD_TO_CART',array(
					'OUT_OF_STOCK'=>lorem_phrase(),
					'ACTION_URL'=>placeholder_url(),
					'PRODUCT_ID'=>placeholder_id(),
					'ALLOW_OPTOUT_TAX'=>lorem_phrase(),
					'PURCHASE_ACTION_URL'=>placeholder_url(),
					'CART_URL'=>placeholder_url(),
						));

		return array(
			lorem_globalise(
				do_lorem_template('CATALOGUE_products_CATEGORY_SCREEN',array(
					'TITLE'=>lorem_title(),
					'DESCRIPTION'=>lorem_sentence(),
					'SUBCATEGORIES'=>$subcategories,
					'CART_LINK'=>$cart_link,
					'ENTRIES'=>lorem_phrase(),
					'TAGS'=>lorem_phrase(),
					'ADD_LINK'=>placeholder_url(),
					'CATALOGUE_GENERIC_ADD'=>lorem_phrase(),
					'CATALOGUE'=>lorem_phrase(),
					'ADD_CAT_URL'=>placeholder_url(),
					'CATALOGUE_GENERIC_ADD_CATEGORY'=>lorem_phrase(),
					'EDIT_CAT_URL'=>placeholder_url(),
					'CATALOGUE_GENERIC_EDIT_CATEGORY'=>lorem_phrase(),
					'_TITLE'=>lorem_phrase(),
					'EDIT_CATALOGUE_URL'=>placeholder_url(),
					'EDIT_CATALOGUE'=>lorem_phrase(),
					'BROWSER'=>$results_browser,
					'TREE'=>lorem_phrase(),
					'ID'=>placeholder_id(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__field_map_products_entry_screen()
	{
		require_lang('shopping');
		require_lang('catalogues');
		require_lang('ecommerce');
		require_css('catalogues');
		require_lang('catalogues');

		$fields = new ocp_tempcode();
		$fields_table = new ocp_tempcode();

		foreach (placeholder_array() as $v)
		{
			$_field = do_lorem_template('CATALOGUE_products_ENTRY_FIELD',array('ENTRYID'=>placeholder_random_id(),'CATALOGUE'=>lorem_phrase(),'TYPE'=>lorem_word(),'FIELD'=>lorem_word(),'FIELDID'=>placeholder_random_id(),'_FIELDID'=>placeholder_random_id(),'FIELDTYPE'=>lorem_word(),'VALUE_PLAIN'=>lorem_phrase(),'VALUE'=>lorem_phrase()),NULL,false,'CATALOGUE_DEFAULT_ENTRY_FIELD');
			$fields->attach($_field);
		}

		$cart_link = do_lorem_template('CATALOGUE_ENTRY_ADD_TO_CART',array(
					'OUT_OF_STOCK'=>lorem_phrase(),
					'ACTION_URL'=>placeholder_url(),
					'PRODUCT_ID'=>placeholder_id(),
					'ALLOW_OPTOUT_TAX'=>lorem_phrase(),
					'PURCHASE_ACTION_URL'=>placeholder_url(),
					'CART_URL'=>placeholder_url(),
						));
		$cart_logo = do_lorem_template('CART_LOGO',array('URL'=>placeholder_url(),'TITLE'=>lorem_phrase()),NULL,false);

		$rating_inside	=	new ocp_tempcode();

		$entry = do_lorem_template('CATALOGUE_products_ENTRY',array(
					'FIELD_0'=>lorem_phrase(),
					'FIELD_1'=>lorem_phrase(),
					'PRODUCT_CODE'=>placeholder_id(),
					'FIELD_9'=>lorem_phrase(),
					'FIELD_2'=>placeholder_number(),
					'PRICE'=>placeholder_number(),
					'RATING'=>$rating_inside,
					'FIELD_7_THUMB'=>placeholder_image(),
					'FIELD_7_PLAIN'=>placeholder_url(),
					'MAP_TABLE'=>placeholder_table(),
					'CART_BUTTONS'=>$cart_link,
					'CART_LINK'=>$cart_logo,
					'FIELDS'=>$fields,
				)
		);

		return array(
			lorem_globalise(
				do_lorem_template('CATALOGUE_products_ENTRY_SCREEN',array(
					'TITLE'=>lorem_title(),
					'WARNINGS'=>'',
					'ENTRY'=>$entry,
					'EDIT_URL'=>placeholder_url(),
					'_EDIT_LINK'=>placeholder_link(),
					'TRACKBACK_DETAILS'=>lorem_phrase(),
					'RATING_DETAILS'=>lorem_phrase(),
					'COMMENT_DETAILS'=>lorem_phrase(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__list_catalogue_products()
	{
		require_lang('shopping');
		require_lang('catalogues');
		require_lang('ecommerce');
		require_css('catalogues');
		require_lang('catalogues');

		$fields = new ocp_tempcode();
		$fields_table = new ocp_tempcode();

		foreach (placeholder_array() as $v)
		{
			$_field = do_lorem_template('CATALOGUE_products_ENTRY_FIELD',array('ENTRYID'=>placeholder_random_id(),'CATALOGUE'=>lorem_phrase(),'TYPE'=>lorem_word(),'FIELD'=>lorem_word(),'FIELDID'=>placeholder_random_id(),'_FIELDID'=>placeholder_random_id(),'FIELDTYPE'=>lorem_word(),'VALUE_PLAIN'=>lorem_phrase(),'VALUE'=>lorem_phrase()),NULL,false,'CATALOGUE_DEFAULT_ENTRY_FIELD');
			$fields->attach($_field);
		}

		$cart_link = do_lorem_template('CATALOGUE_ENTRY_ADD_TO_CART',array(
					'OUT_OF_STOCK'=>lorem_phrase(),
					'ACTION_URL'=>placeholder_url(),
					'PRODUCT_ID'=>placeholder_id(),
					'ALLOW_OPTOUT_TAX'=>lorem_phrase(),
					'PURCHASE_ACTION_URL'=>placeholder_url(),
					'CART_URL'=>placeholder_url(),
						));
		$cart_logo = do_lorem_template('CART_LOGO',array('URL'=>placeholder_url(),'TITLE'=>lorem_phrase()),NULL,false);

		$rating_inside	=	new ocp_tempcode();

		$entry=do_lorem_template('CATALOGUE_products_ENTRY_EMBED',array(
					'FIELD_0'=>lorem_phrase(),
					'FIELD_1'=>lorem_phrase(),
					'PRODUCT_CODE'=>placeholder_id(),
					'FIELD_9'=>lorem_phrase(),
					'FIELD_2'=>placeholder_number(),
					'PRICE'=>placeholder_number(),
					'RATING'=>$rating_inside,
					'FIELD_7_THUMB'=>placeholder_image(),
					'FIELD_7_PLAIN'=>placeholder_url(),
					'MAP_TABLE'=>placeholder_table(),
					'CART_BUTTONS'=>$cart_link,
					'CART_LINK'=>$cart_logo,
					'FIELDS'=>$fields,
					'URL'=>placeholder_url(),
					'VIEW_URL'=>placeholder_url(),
					)
		);

		return array(
			lorem_globalise(
				$entry
			,NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__list_catalogue_screen_products()
	{
		require_lang('catalogues');
		require_css('catalogues');

		$all_rating_criteria=array();
		$all_rating_criteria[]=array('TITLE'=>lorem_word(),'RATING'=>make_string_tempcode("6"),'TYPE'=>lorem_word());

		$line = new ocp_tempcode();
		foreach (placeholder_array(1) as $v)
		{
		$line->attach(do_lorem_template('CATALOGUE_products_LINE',array(
					'FIELD_0'=>lorem_phrase(),
					'FIELD_7_THUMB'=>lorem_phrase(),
					'RATING'=>new ocp_tempcode(),
					'FIELD_2'=>placeholder_number(),
					'ADD_TO_CART'=>placeholder_url(),
					'VIEW_URL'=>placeholder_url(),
					'GO_FOR_IT'=>lorem_phrase(),
						)
			));
		}
		$entry = do_lorem_template('CATALOGUE_products_LINE_WRAP',array(
					'CONTENT'=>$line,
						)
			);

		return array(
			lorem_globalise(
				do_lorem_template('CATALOGUE_DEFAULT_CATEGORY_SCREEN',array(
					'ID'=>placeholder_random_id(),
					'ADD_DATE_RAW'=>placeholder_time(),
					'TITLE'=>lorem_title(),
					'_TITLE'=>lorem_phrase(),
					'TAGS'=>'',
					'CATALOGUE'=>lorem_word_2(),
					'BROWSER'=>'',
					'SORTING'=>'',
					'ADD_LINK'=>placeholder_url(),
					'ADD_CAT_URL'=>placeholder_url(),
					'EDIT_CAT_URL'=>placeholder_url(),
					'EDIT_CATALOGUE_URL'=>placeholder_url(),
					'ENTRIES'=>$entry,
					'SUBCATEGORIES'=>'',
					'DESCRIPTION'=>lorem_sentence(),
					'CART_LINK'=>placeholder_url(),
					'TREE'=>lorem_phrase(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__results_products_table()
	{
		require_css('catalogues');

		return array(
			lorem_globalise(
				do_lorem_template('RESULTS_products_TABLE',array(
					'FIELDS'=>lorem_phrase(),
					'FIELDS_TITLE'=>lorem_phrase(),
					'MESSAGE'=>lorem_phrase(),
					'WIDTHS'=>array(placeholder_number()),
						)
			),NULL,'',true),
		);
	}
}
