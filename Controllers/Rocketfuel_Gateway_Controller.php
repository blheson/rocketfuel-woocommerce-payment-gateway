<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

class Rocketfuel_Gateway_Controller extends \WC_Payment_Gateway
{
	public function __construct()
	{
		$this->id = 'rocketfuel_gateway';

		// $this->icon = 'rkfl.png';

		$this->has_fields = false;

		$this->method_title = 'Rocketfuel';

		$this->method_description = 'Pay with Crypto using Rocketfuel';

		$this->init_form_fields();

		$this->init_settings();

		$this->title = $this->get_option('title');


		$this->environment = $this->get_option('environment');

		$this->endpoint = $this->get_endpoint($this->environment);

		$this->public_key = $this->get_option('public_key');

		$this->description = $this->get_option('description');

		$this->password = $this->get_option('password');

		$this->button_text = $this->get_option('button_text') ? $this->get_option('button_text') : 'Pay with Crypto';

		$this->email = $this->get_option('email');

		$this->payment_complete_order_status = $this->get_option('payment_complete_order_status') ? $this->get_option('payment_complete_order_status') : 'completed';

		$this->supports = array(
			'products',
			'refunds',
			'tokenization',
			'subscriptions',
			'multiple_subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
		);


		$this->merchant_id = $this->get_option('merchant_id');

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('admin_notices', array($this, 'admin_notices'));
		// add_action('woocommerce_review_order_after_submit', array($this, 'rocketfuel_place_order'));
		add_action('woocommerce_review_order_before_submit', array($this, 'rocketfuel_place_order'));
	}

	public function get_endpoint($environment)
	{

		$environment_data = array(
			'prod' => 'https://app.rocketfuelblockchain.com/api',
			'dev' => 'https://dev-app.rocketdemo.net/api',
			'stage2' => 'https://qa-app.rocketdemo.net/api',
			'preprod' => 'https://preprod-app.rocketdemo.net/api',
			'local' => 'http://e661-102-89-41-62.ngrok.io/api',
			'sandbox' => 'https://app-sandbox.rocketfuelblockchain.com/api',
			'local' => 'http://localhost:3001/api',

		);

		return isset($environment_data[$environment]) ? $environment_data[$environment] : 'https://app.rocketfuelblockchain.com/api';
	}
	public function init_form_fields()
	{
		$all_wc_order_status = wc_get_order_statuses();

		$this->form_fields = apply_filters('rocketfuel_admin_fields', array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'rocketfuel-payment-gateway'),
				'type' => 'checkbox',
				'label' => __('Enable Rocketfuel', 'rocketfuel-payment-gateway'),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __('Title', 'rocketfuel-payment-gateway'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'rocketfuel-payment-gateway'),
				'default' => __('Rocketfuel', 'rocketfuel-payment-gateway'),
				'desc_tip'      => true,
			),

			'environment' => array(
				'title' => __('Working environment', 'rocketfuel-payment-gateway'),
				'type' => 'select',
				'default' => 'prod',
				'options' =>  array(
					'prod' => 'Production',
					'dev' => 'Development',
					'stage2' => 'QA',
					'preprod' => 'Pre-Production',
					'sandbox' => 'Sandbox',
					'local' => 'local'
				)
			),
			'description' => array(
				'title' => __('Customer Message', 'rocketfuel-payment-gateway'),
				'type' => 'textarea',
				'default' => 'Pay for your order with RocketFuel'
			),
			'merchant_id' => array(
				'title' => __('Merchant ID', 'rocketfuel-payment-gateway'),
				'type' => 'text',
				'default' => ''
			),
			'public_key' => array(
				'title' => __('Public Key', 'rocketfuel-payment-gateway'),
				'type' => 'textarea',
				'default' => ''
			), 'email' => array(
				'title' => __('Email', 'rocketfuel-payment-gateway'),
				'type' => 'text',
				'default' => ''
			), 'password' => array(
				'title' => __('Password', 'rocketfuel-payment-gateway'),
				'type' => 'password',
				'default' => ''
			),
			'payment_complete_order_status' => array(
				'title' => __('Order Status for Completed Payment', 'rocketfuel-payment-gateway'),
				'type' => 'select',
				'default' => 'wc-completed',
				'options' =>  $all_wc_order_status
			),
			'callback_url' => array(
				'title' => __('Callback URL', 'rocketfuel-payment-gateway'),
				'type' => 'checkbox',
				'label' => esc_url(rest_url() . Plugin::get_api_route_namespace() . '/payment'),
				'description' => __('Callback URL for Rocketfuel', 'rocketfuel-payment-gateway'),
				'default' => '',
				'css' => 'display:none'
				// 'desc_tip'      => true,
			),
			'button_text' => array(
				'title' => __('Button Text', 'rocketfuel-payment-gateway'),
				'type' => 'text',
				'default' => 'Pay with Crypto',
				'required' => true,
			),
		));
	}
	public function merchant_auth()
	{
		return $this->get_encrypted($this->merchant_id);
	}
	/**
	 * Rocketfuel Place order Button
	 * @return void
	 */
	public function rocketfuel_place_order()
	{
		// $0.style.setProperty('visibility', 'hidden', true ? 'important' : '');
		if (!$this->password || !$this->email) {
			echo '<span style="color:red">' . __('Vendor should fill in the settings page to start using Rocketfuel', 'rocketfuel-payment-gateway') . '</span>';
			return;
		}
		wp_enqueue_script('wc-gateway-rkfl-script');

		wp_enqueue_script('wc-gateway-rkfl-payment-buttons');


		$temp_orderid_rocketfuel = '';

?>



		<div>
			<div id="rocketfuel_retrigger_payment_button" class="rocketfuel_retrigger_payment_button" data-rkfl-button-text="<?php echo esc_attr($this->button_text); ?>"><?php echo esc_html($this->button_text); ?></div>
			<input type="hidden" name="merchant_auth_rocketfuel" value="<?php echo esc_attr($this->merchant_auth()); ?>">
			<input type="hidden" name="encrypted_req_rocketfuel" value="">


			<input type="hidden" name="payment_status_rocketfuel" value="pending">

			<input type="hidden" name="payment_complete_order_status" value="<?php echo esc_attr($this->payment_complete_order_status); ?>">


			<input type="hidden" name="temp_orderid_rocketfuel" value="<?php echo esc_attr($temp_orderid_rocketfuel); ?>">

			<input type="hidden" name="order_status_rocketfuel" value="wc-on-hold">

			<input type="hidden" name="environment_rocketfuel" value="<?php echo  esc_attr($this->environment); ?>">

			<script src="<?php echo esc_url(Plugin::get_url('assets/js/rkfl-iframe.js?ver=' . microtime())); ?>">
			</script>

		</div>


<?php

	}

	/**
	 * Check if product is a subscription product
	 * @param WC_Products $product
	 * @return bool
	 */
	public function is_subscription_product($product)
	{
		try {

			return class_exists('WC_Subscriptions_Product') && \WC_Subscriptions_Product::is_subscription($product);
		} catch (\Throwable $th) {

			return false;
		}
	}
	public function calculate_frequency($_product_meta)
	{

		$frequency = false;

		if ($_product_meta['_subscription_period'][0] === 'week' && (int)$_product_meta['_subscription_period_interval'][0] === 1) {
			$frequency = 'weekly';
		}
		if ($_product_meta['_subscription_period'][0] === 'day' && (int)$_product_meta['_subscription_period_interval'][0] === 1) {
			$frequency = 'daily';
		}
		if ($_product_meta['_subscription_period'][0] === 'month') {
			if ((int)$_product_meta['_subscription_period_interval'][0] === 1) {
				$frequency = 'monthly';
			} else if ((int)$_product_meta['_subscription_period_interval'][0] === 3) {
				$frequency = 'quarterly';
			} else if ((int)$_product_meta['_subscription_period_interval'][0] === 6) {
				$frequency = 'half-yearly';
			} else if ((int)$_product_meta['_subscription_period_interval'][0] === 12) {
				$frequency = 'yearly';
			}
		}
		if ($_product_meta['_subscription_period'][0] === 'year' && (int)$_product_meta['_subscription_period_interval'][0] === 1) {
			$frequency = 'yearly';
		}
		return $frequency;
	}

	/**
	 * Parse cart items and prepare for order
	 * @param array $items 
	 * @param string $temp_orderid
	 * @return array
	 */
	public function sort_cart($items, $temp_orderid)
	{
		$data = array();
		foreach ($items as $cart_item) {
			$temp_data = array(
				'name' => $cart_item['data']->get_title(),
				'id' => (string)$cart_item['product_id'],
				'price' => $cart_item['data']->get_price(),
				'quantity' => (string)$cart_item['quantity']
			);



			// Mock subscription 
			$_product = wc_get_product($cart_item['product_id']);

			if ($_product && $this->is_subscription_product($_product)) {

				$_product_meta = get_post_meta($cart_item['product_id']);

				if ($_product_meta && is_array($_product_meta)) {


					$frequency = $this->calculate_frequency($_product_meta);

					if ($frequency) {

						$new_array = array_merge(
							$temp_data,
							array(

								'isSubscription' => true,

								'frequency' => $frequency,

								'subscriptionPeriod' => $_product_meta['_subscription_length'][0] . $_product_meta['_subscription_period'][0][0],

								'merchantSubscriptionId' => (string) $temp_orderid . '-' . $cart_item['product_id'],

								'autoRenewal' => true
							)
						);
					} else {
						$new_array = $temp_data;
					}
				}
			} else {

				$new_array = $temp_data;
			}
			$data[] = $new_array;
		}

		try {

			if (
				(null !== WC()->cart->get_shipping_total()) &&
				(!strpos(strtolower(WC()->cart->get_shipping_total()), 'free')) &&
				(int) WC()->cart->get_shipping_total() > 0
			) {

				$data[] = array(
					'name' => 'Shipping',
					'id' => microtime(),
					'price' => WC()->cart->get_shipping_total(),
					'quantity' => '1'
				);
			}
		} catch (\Throwable $th) {
			// silently ignore
		}

		return $data;
	}

	/**
	 * Update order when payment has been confirmed
	 * @param WP_REST_REQUEST $request_data
	 * @return void
	 */
	public function update_order($request_data)
	{

		$data = $request_data->get_params();

		if (!$data['order_id'] || !$data['status']) {

			echo json_encode(array('status' => 'failed', 'message' => 'Order was not updated. Invalid parameter. You must pass in order_id and status'));

			exit;
		}

		$order_id = $data['order_id'];

		$status = $data['status'];

		$order = wc_get_order($order_id);

		if (!$order) {
			echo json_encode(array('status' => 'failed', 'message' => 'Order was not updated. Could not retrieve order from the order_id that was sent'));
			exit;
		}

		if ($status === 'admin_default') {
			$status = $this->payment_complete_order_status;
		}

		$data = $order->update_status($status);

		$order->payment_complete();

		echo json_encode(array('status' => 'success', 'message' => 'Order was updated'));
		exit;
	}

	/**
	 * Process payment and redirect user to payment page
	 * @param int $order_id
	 * @return false|array
	 */
	public function process_payment($order_id)
	{
		global $woocommerce;

		$order = wc_get_order($order_id);

		// $cart = $this->sort_cart(WC()->cart->get_cart());

		// $this->swap_order_id($temporary_order_id, $order_id);
		// Remove cart

		// Return thankyou redirect
		$buildUrl = $this->get_return_url($order);
		return array(
			'result' => 'success',
			'redirect' => $buildUrl
		);
	}
	/**
	 * Swap temporary order for new order Id
	 * @param int $temp_order_id
	 * @param int $new_order_id
	 * 
	 * @return true
	 */
	public function swap_order_id($temp_order_id, $new_order_id)
	{

		$data = json_encode(array('tempOrderId' =>
		$temp_order_id, 'newOrderId' => $new_order_id));

		$order_payload = $this->get_encrypted($data, false);

		$merchant_id = base64_encode($this->merchant_id);

		$body = wp_json_encode(array('merchantAuth' => $order_payload, 'merchantId' => $merchant_id));


		$args = array(
			'timeout'	=> 45,
			'headers' => array('Content-Type' => 'application/json'),
			'body' => $body
		);


		$response = wp_remote_post($this->endpoint . '/update/orderId', $args);


		$response_code = wp_remote_retrieve_response_code($response);

		$response_body = wp_remote_retrieve_body($response);

		return true;
	}

	/**
	 * Encrypt Data
	 *
	 * @param $to_crypt string to encrypt
	 * @return string|false
	 */
	public function get_encrypted($to_crypt, $general_public_key = true)
	{

		$out = '';

		if ($general_public_key) {
			$pub_key_path = dirname(__FILE__) . '/rf.pub';

			if (!file_exists($pub_key_path)) {
				return false;
			}
			$cert =  file_get_contents($pub_key_path);
		} else {
			$cert = $this->public_key;
		}


		$public_key = openssl_pkey_get_public($cert);

		$key_length = openssl_pkey_get_details($public_key);

		$part_len = $key_length['bits'] / 8 - 11;
		$parts = str_split($to_crypt, $part_len);

		foreach ($parts as $part) {
			$encrypted_temp = '';
			openssl_public_encrypt($part, $encrypted_temp, $public_key, OPENSSL_PKCS1_OAEP_PADDING);
			$out .=  $encrypted_temp;
		}

		return base64_encode($out);
	}
	/**
	 * Check if Rocketfuel merchant details is filled.
	 */
	public function admin_notices()
	{

		if ($this->enabled == 'no') {
			return;
		}

		// Check required fields.
		if (!($this->public_key && $this->password)) {
			echo '<div class="error"><p>' . sprintf(__('Please enter your Rocketfuel merchant details <a href="%s">here</a> to be able to use the Rocketfuel WooCommerce plugin.', 'rocketfuel-payment-gateway'), admin_url('admin.php?page=wc-settings&tab=checkout&section=rocketfuel_gateway')) . '</p></div>';
			return;
		}
	}
}
