<?php
/**
* Plugin Name: Secapay Form Gateway for WooCommerce
* Plugin URI: http://www.secapay.com/
* Description: WooCommerce Plugin for accepting payment through Secapay Form Gateway.
* Version: 0.0.1
* Author:
* Author URI: http://www.secapay.com
* Contributors:
* Requires at least: 4.0
*
*
*
*
* @package Secapay Form Gateway for WooCommerce
*
*/
add_action('plugins_loaded', 'init_woocommerce_secapayform', 0);

function init_woocommerce_secapayform() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }

	load_plugin_textdomain('woo-secapayform-bols', false, dirname( plugin_basename( __FILE__ ) ) . '/lang');

	class woocommerce_secapayform extends WC_Payment_Gateway {

		public function __construct() {
			global $woocommerce;

			$this->id			= 'secapayform';
			$this->method_title = __( 'Secapay Form', 'woo-secapayform-bols' );
			$this->icon			= apply_filters( 'woocommerce_secapayform_icon', '' );
			$this->has_fields 	= false;
			
			// Load the form fields.
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			// Define user set variables
			$this->title 		= $this->settings['title'];
			$this->description 	= $this->settings['description'];
			$this->vendor_name  = $this->settings['vendorname'];
			$this->button_link = $this->settings['button_link'];
			$this->notify_url   = str_replace( 'https:', 'http:', home_url( '/wc-api/woocommerce_secapayform' ) );

			// Actions
			add_action( 'init', array( $this, 'successful_request' ) );
			add_action( 'woocommerce_api_woocommerce_secapayform', array( $this, 'successful_request' ) );
			add_action( 'woocommerce_receipt_secapayform', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}
		
		/**
		* Admin Panel Options
		* - Options for bits like 'title' and availability on a country-by-country basis
		*
		* @since 1.0.0
		*/

public function admin_options() {
	?>
	<h3><?php _e('Secapay Form', 'woo-secapayform-bols'); ?></h3>
	<p><?php _e('Secapay Form works by sending the user to Secapay to enter their payment information.' , 'woo-secapayform-bols'); ?></p>
	<table class="form-table">
	<?php

		// Generate the HTML For the settings form.
		$this->generate_settings_html();
	?>
	</table><!--/.form-table-->
	<?php
	} // End admin_options()

	/**
	* Initialise Gateway Settings Form Fields
	*/
	function init_form_fields() {
	$this->form_fields = array(
				'enabled' => array(
					'title' => __( 'Enable/Disable', 'woo-secapayform-bols' ),
					'type' => 'checkbox',
					'label' => __( 'Enable Secapay Form', 'woo-secapayform-bols' ),
					'default' => 'yes'
				),
				'title' => array(
					'title' => __( 'Title', 'woo-secapayform-bols' ),
					'type' => 'readonly',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woo-secapayform-bols' ),
					'default' => __( 'Secapay', 'woo-secapayform-bols' )
				),
				'description' => array(
					'title' => __( 'Description', 'woo-secapayform-bols' ),
					'type' => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'woo-secapayform-bols' ),
					'default' => __("Pay via Secapay; you can pay with your Credit Card.", 'woo-secapayform-bols')
				),
				'vendorname' => array(
					'title' => __( 'Merchant Name', 'woo-secapayform-bols' ),
					'type' => 'text',
					'description' => __( 'Please enter your merchant name.', 'woo-secapayform-bols' ),
					'default' => ''
				),

				'button_link' => array(
					'title' => __( 'Button Link', 'woo-secapayform-bols' ),
					'type' => 'text',
					'description' => __( 'Please enter your button link provided by Secapay.', 'woo-secapayform-bols' ),
					'default' => ''
				),
			
			);
		} // End init_form_fields()

	/**
	* There are no payment fields for secapayform, but we want to show the description if set.
	**/
	function payment_fields() {
	if ($this->description) echo wpautop(wptexturize($this->description));
	}

	/**
	* Generate the nochex button link
	**/

	public function generate_secapayform_form( $order_id ) {

		global $woocommerce;

		$order = new WC_Order( $order_id );
	
		$basket = '';

		// Cart Contents
		$item_loop = 0;

		if ( sizeof( $order->get_items() ) > 0 ) {
			foreach ( $order->get_items() as $item ) {
				if ( $item['qty'] ) {

					$item_loop++;

					$product = $order->get_product_from_item( $item );

						$item_name 	= $item['name'];

					$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );

					if ( $meta = $item_meta->display( true, true ) )
						$item_name .= ' ( ' . $meta . ' )';

					$item_cost = $order->get_item_subtotal( $item, false );

					$item_total_inc_tax = $order->get_item_subtotal( $item, true )*$item['qty'];
					$item_total = $order->get_item_subtotal( $item, false )*$item['qty'];

					//$item_sub_total =
					$item_tax = number_format( (float) ($item_total_inc_tax - $item_total)/$item['qty'], 2, '.', '' );

					if($item_loop > 1){
						$basket .= ':';
					}
					$sku = '';
					if ( $product->get_sku() ) {
						$sku = '['.$product->get_sku().']';
					}

					$basket .= str_replace(':',' = ',$sku).str_replace(':',' = ',$item_name).':'.$item['qty'].':'.$item_cost.':'.$item_tax.':'.number_format( $item_cost+$item_tax, 2, '.', '' ).':'.$item_total_inc_tax;
				}
			}
		}


		// Fees
		if ( sizeof( $order->get_fees() ) > 0 ) {
			foreach ( $order->get_fees() as $item ) {
				$item_loop++;

				$basket .= ':'.str_replace(':',' = ',$item['name']).':1:'.$item['line_total'].':---:'.$item['line_total'].':'.$item['line_total'];
			}
		}

		// Shipping Cost item - paypal only allows shipping per item, we want to send shipping for the order
		if ( $order->get_total_shipping() > 0 ) {
			$item_loop++;

			$ship_exc_tax = number_format( $order->get_total_shipping(), 2, '.', '' );

			$basket .= ':'.__( 'Shipping via', 'woo-secapayform-bols' ) . ' ' . str_replace(':',' = ',ucwords( $order->get_shipping_method() )).':1:'.$ship_exc_tax.':'.$order->get_shipping_tax().':'.number_format( $ship_exc_tax+$order->get_shipping_tax(), 2, '.', '' ).':'.number_format( $order->get_total_shipping()+$order->get_shipping_tax(), 2, '.', '' );
		}

		// Discount
		if ( $order->get_total_discount() > 0 ){
			$item_loop++;

			$basket .= ':Discount:---:---:---:---:-'.$order->get_total_discount();
		}
	
		$item_loop++;
		$basket .= ':Order Total:---:---:---:---:'.$order->get_total();

		$basket = $item_loop.':'.$basket;

		$time_stamp = date("ymdHis");
		$orderid = $this->vendor_name . "-" . $time_stamp . "-" . $order_id;

		$secapay_arg['ReferrerID'] 			= 'CC923B06-40D5-4713-85C1-700D690550BF';
		$secapay_arg['Amount'] 				= $order->get_total();
		$secapay_arg['CustomerName']		= substr($order->billing_first_name.' '.$order->billing_last_name, 0, 100);
		$secapay_arg['CustomerEMail'] 		= substr($order->billing_email, 0, 255);
		$secapay_arg['BillingSurname'] 		= substr($order->billing_last_name, 0, 20);
		$secapay_arg['BillingFirstnames'] 	= substr($order->billing_first_name, 0, 20);
		$secapay_arg['BillingAddress1'] 	= substr($order->billing_address_1, 0, 100);
		$secapay_arg['BillingAddress2'] 	= substr($order->billing_address_2, 0, 100);
		$secapay_arg['BillingCity'] 		= substr($order->billing_city, 0, 40);
		if( $order->billing_country == 'US' )
		{
			$secapay_arg['BillingState'] 	= $order->billing_state;
		}
		else
		{
			$secapay_arg['BillingState'] 	= '';
		}
		$secapay_arg['BillingPostCode'] 	= substr($order->billing_postcode, 0, 10);
		$secapay_arg['BillingCountry'] 		= $order->billing_country;
		$secapay_arg['BillingPhone'] 		= substr($order->billing_phone, 0, 20);

		if( $this->cart_has_virtual_product() == true){
				$secapay_arg['DeliverySurname'] 	= $order->billing_last_name;
				$secapay_arg['DeliveryFirstnames'] 	= $order->billing_first_name;
				$secapay_arg['DeliveryAddress1'] 	= $order->billing_address_1;
				$secapay_arg['DeliveryAddress2'] 	= $order->billing_address_2;
				$secapay_arg['DeliveryCity'] 		= $order->billing_city;
			if( $order->billing_country == 'US' )
			{
				$secapay_arg['DeliveryState'] 	= $order->billing_state;
			}
			else
			{
				$secapay_arg['DeliveryState'] 	= '';
			}
			$secapay_arg['DeliveryPostCode'] 	= $order->billing_postcode;
			$secapay_arg['DeliveryCountry'] 	= $order->billing_country;
		}
		else
		{
			$secapay_arg['DeliverySurname'] 	= $order->shipping_last_name;
			$secapay_arg['DeliveryFirstnames'] 	= $order->shipping_first_name;
			$secapay_arg['DeliveryAddress1'] 	= $order->shipping_address_1;
			$secapay_arg['DeliveryAddress2'] 	= $order->shipping_address_2;
			$secapay_arg['DeliveryCity'] 		= $order->shipping_city;
			if( $order->shipping_country == 'US' )
			{
				$secapay_arg['DeliveryState'] 	= $order->shipping_state;
			}
			else
			{
				$secapay_arg['DeliveryState'] 	= '';
			}
			$secapay_arg['DeliveryPostCode'] 	= $order->shipping_postcode;
			$secapay_arg['DeliveryCountry'] 	= $order->shipping_country;
		}

		$secapay_arg['DeliveryPhone'] 		= substr($order->billing_phone, 0, 20);
		$secapay_arg['FailureURL'] 			= $this->notify_url;
		$secapay_arg['SuccessURL'] 			= $this->notify_url;
		$secapay_arg['Description'] 		= sprintf(__('Order #%s' , 'woo-secapayform-bols'), ltrim( $order->get_order_number(), '#' ));
		$secapay_arg['Currency'] 			= get_woocommerce_currency();
		$secapay_arg['Basket'] 				= $basket;

	$post_values = "";
	foreach( $secapay_arg as $key => $value ) {
	$post_values .= "$key=" . trim( $value ) . "&";
	}
	$post_values = substr($post_values, 0, -1);

	$secapay_arg_array = array();

		wc_enqueue_js('
			jQuery("body").block({
					message: "'.__('Thank you for your order. We are now redirecting you to Secapay Form to make payment.', 'woo-secapayform-bols').'",
					overlayCSS:
					{
						background: "#fff",
						opacity: 0.6
					},
					css: {
				padding:        20,
				textAlign:      "center",
				color:          "#555",
				border:         "3px solid #aaa",
				backgroundColor:"#fff",
				cursor:         "wait",
						lineHeight:		"32px"
				}
				});
			jQuery("#submit_secapay_payment_form").click();
		');

		$total = $this->get_order_total();

		$nwbutton = str_replace("50", $total, $this->button_link);

		$newbutton = $nwbutton .  '&redirect_url=' . $this->notify_url . '?order_id=' . $order->id;
	
		$url = $order->get_checkout_payment_url();

		return  '<form action="'.esc_url($newbutton).'" method="post" id="secapay_payment_form">
					' . implode('', $secapay_arg_array) . '
					<input type="submit" class="button" id="submit_secapay_payment_form" value="'.__('Pay via Secapay', 'woo-secapayform-bols').'" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__('Cancel order &amp; restore cart', 'woo-secapayform-bols').'</a>
			</form>';

	}

	/**
	* Process the payment and return the result
	**/
	function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
		);

	}

	/**
	* receipt_page
	**/
	function receipt_page( $order ) {

		echo '<p>'.__('Thank you for your order, please click the button below to pay with Secapay Form.', 'woo-secapayform-bols').'</p>';

		echo $this->generate_secapayform_form( $order );

	}
	

	/**
	 * Successful Payment!
	 **/
	function successful_request() {

		global $woocommerce;

		$ref= "";

		if ( isset($_REQUEST['secapay_ref']) && !empty($_REQUEST['order_id']) ) {

	 	$ref = $_REQUEST['secapay_ref'];

	 	$order_id = $_REQUEST['order_id'];
	   }

		$order = new WC_Order($order_id);

		if ($order) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_URL, 'https://secapay.com/transactions/status/'.$ref);
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			$response = curl_exec($curl);

			if (!$response) {
				$url = $order->get_checkout_payment_url();
			} 

			else
			{
				$response = json_decode($response, true);
				$status = strtoupper($response['name']);
				
				switch($status) {
					case 'SUCCESS':
				
					$order->update_status('completed');

					$url = $this->get_return_url($order);

					break;

					case 'FAILED':

					$order->update_status('pending payment');

					$url = $order->get_checkout_payment_url();
					break;

					case 'PENDING':
					$order->update_status('failed');

					$url = $order->get_checkout_payment_url();
					break;
				}
			}
			curl_close($curl);
			wp_redirect($url);
		} else {
			wc_add_notice( sprintf(__('Transaction Failed. The Error Message was %s', 'woo-secapayform-bols'), $ref['StatusDetail'] ), $notice_type = 'error' );
			wp_redirect($url);
		}
		wp_redirect($url);

	}
	

	private function pkcs5_pad($text, $blocksize)	{
			$pad = $blocksize - (strlen($text) % $blocksize);
			return $text . str_repeat(chr($pad), $pad);
	}


	/**
	* Check if the cart contains virtual product
	*
	* @return bool
	*/
	private function cart_has_virtual_product() {
		global $woocommerce;
		$has_virtual_products = false;
		$virtual_products = 0;
		$products = $woocommerce->cart->get_cart();
		foreach( $products as $product ) {
			$product_id = $product['product_id'];
			$is_virtual = get_post_meta( $product_id, '_virtual', true );
			// Update $has_virtual_product if product is virtual
			if( $is_virtual == 'yes' )
			$virtual_products += 1;
		}
		if( count($products) == $virtual_products ){
			$has_virtual_products = true;
		}
		return $has_virtual_products;
	}

	private function force_ssl($url){

		if ( 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) ) {
			$url = str_replace( 'http:', 'https:', $url );
		}

		return $url;
	}

	}

	/**
	* Add the gateway to WooCommerce
	**/
	function add_secapayform_gateway( $methods ) {
	$methods[] = 'woocommerce_secapayform'; return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'add_secapayform_gateway' );

	}