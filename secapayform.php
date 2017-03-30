<?php
/**
* Plugin Name: Secapay Form Gateway for WooCommerce
* Plugin URI: http://www.secapay.com/
* Description: WooCommerce Plugin for accepting payment through Secapay Gateway.
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
			$this->method_title = __( 'Secapay', 'woo-secapayform-bols' );
			$this->icon			= apply_filters( 'woocommerce_secapayform_icon', '' );
			$this->has_fields 	= false;
			
			// Load the form fields.
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			// Define user set variables
			$this->title 		= $this->settings['title'];
			$this->description 	= $this->settings['description'];
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
	<h3><?php _e('Secapay', 'woo-secapayform-bols'); ?></h3>
	<p><?php _e('Secapay works by sending the user to Secapay to enter their payment information.' , 'woo-secapayform-bols'); ?></p>
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
					'label' => __( 'Enable Secapay', 'woo-secapayform-bols' ),
					'default' => 'yes'
				),
				'title' => array(
					'title' => __( 'Title', 'woo-secapayform-bols' ),
					'type' => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woo-secapayform-bols' ),
					'default' => __( 'Secapay', 'woo-secapayform-bols' )
				),
				'description' => array(
					'title' => __( 'Description', 'woo-secapayform-bols' ),
					'type' => 'text',
					'description' => __( 'This controls the description which the user sees during checkout.', 'woo-secapayform-bols' ),
					'default' => __("Pay via Secapay; you can pay with your Credit Card.", 'woo-secapayform-bols')
				),
			
				'button_link' => array(
					'title' => __( 'Button ID', 'woo-secapayform-bols' ),
					'type' => 'text',
					'description' => __( 'Please enter your button ID provided by Secapay.', 'woo-secapayform-bols' ),
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
	
		
		wc_enqueue_js('
			jQuery("body").block({
					message: "'.__('Directing to Secapay to make Payment.', 'woo-secapayform-bols').'",
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

		echo '<p>'.__('Thank you for your order, please click the button below to pay with Secapay.', 'woo-secapayform-bols').'</p>';

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
		} 
		else
		 {
			wc_add_notice( sprintf(__('Transaction Failed. The Error Message was %s', 'woo-secapayform-bols'), $ref['StatusDetail'] ), $notice_type = 'error' );
			wp_redirect($url);
		}
		wp_redirect($url);

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