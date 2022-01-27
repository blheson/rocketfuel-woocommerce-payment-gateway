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

		$this->email = $this->get_option('email');

		$this->payment_complete_order_status = $this->get_option('payment_complete_order_status') ? $this->get_option('payment_complete_order_status') : 'completed';

		$this->supports = array('products');

		$this->merchant_id = $this->get_option('merchant_id');

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	}
	public function get_endpoint($environment)
	{
		$environment_data = array(
			'prod' => 'https://app.rocketfuelblockchain.com/api',
			'dev' => 'https://dev-app.rocketdemo.net/api',
			'stage2' => 'https://qa-app.rocketdemo.net/api',
			'preprod' => 'https://preprod-app.rocketdemo.net/api',
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
					'preprod' => 'Pre-Production'
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
			)
		));
	}
	/**
	 * Parse cart items and prepare for order
	 * @param array $items 
	 * @return array
	 */
	public function sortCart($items)
	{
		$data = array();
		foreach ($items as $cart_item) {
			$data[] = array(
				'name' => $cart_item['data']->get_title(),
				'id' => (string)$cart_item['product_id'],
				'price' => $cart_item['data']->get_price(),
				'quantity' => (string)$cart_item['quantity']
			);
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

		if ($status === 'admin_default')
			$status = $this->payment_complete_order_status;

		$data = $order->update_status($status);
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
		$cart = $this->sortCart(WC()->cart->get_cart());

		$user_data = base64_encode(json_encode(array(
			'first_name' => $order->get_billing_first_name(),
			'last_name' => $order->get_billing_last_name(),
			'email' => $order->get_billing_email(),
			'merchant_auth' => 	$this->merchant_auth()
		)));
		$merchant_cred = array(
			'email' => $this->email,
			'password' => $this->password
		);
		$data = array(
			'cred' => $merchant_cred,
			'endpoint' => $this->endpoint,
			'body' => array(
				'amount' => $order->get_total(),
				'cart' => $cart,
				'merchant_id' => $this->merchant_id,
				'currency' => "USD",
				'order' => (string) $order_id,
				'redirectUrl' => ''
			)
		);

		$payment_response = Process_Payment_Controller::process_payment($data);

		if (!$payment_response) {
			return;
		}

		$result = json_decode($payment_response);

		if (!isset($result->result) && !isset($result->result->url)) {
			wc_add_notice(__('Failed to place order', 'rocketfuel-payment-gateway'), 'error');
			return false;
		}
		$urlArr = explode('/', $result->result->url);
		$uuid = $urlArr[count($urlArr) - 1];

		// Remove cart
		$woocommerce->cart->empty_cart();
		// Return thankyou redirect
		// $pay_link = get_permalink(get_option(Plugin::$prefix . 'process_payment_page_id' ));
		// $order_key = explode( 'order-received', $this->get_return_url($order))[1];
		$buildUrl = $this->get_return_url($order) . "&uuid=" . $uuid . "&user_data=" . $user_data;

		if ($this->environment !== 'prod') {

			$buildUrl .= '&env=' . $this->environment;
		}

		return array(
			'result' => 'success',
			'redirect' => $buildUrl
		);
	}
	public function merchant_auth()
	{
		return $this->get_encrypted($this->merchant_id);
	}
	/**
	 * Encrypt Data
	 *
	 * @param $to_crypt string to encrypt
	 * @return string
	 */
	public function get_encrypted($to_crypt)
	{

		$out = '';

		$pub_key_path = dirname(__FILE__) . '/rf.pub';

		if (!file_exists($pub_key_path)) {
			return false;
		}
		$cert = file_get_contents($pub_key_path);

		$public_key = openssl_pkey_get_public($cert);

		$key_lenght = openssl_pkey_get_details($public_key);

		$part_len = $key_lenght['bits'] / 8 - 11;
		$parts = str_split($to_crypt, $part_len);
		foreach ($parts as $part) {
			$encrypted_temp = '';
			openssl_public_encrypt($part, $encrypted_temp, $public_key, OPENSSL_PKCS1_OAEP_PADDING);
			$out .=  $encrypted_temp;
		}

		return base64_encode($out);
	}
}
