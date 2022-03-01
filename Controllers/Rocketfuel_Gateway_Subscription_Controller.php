<?php

namespace Rocketfuel_Gateway\Controllers;

if (!defined('ABSPATH')) {
	exit;
}
/**
 * Class Rocketfuel_Gateway_Subscription_Controller 
 */
class Rocketfuel_Gateway_Subscription_Controller extends Rocketfuel_Gateway_Controller
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if (class_exists('WC_Subscriptions_Order')) {

			add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
		}
	}

	/**
	 * Check if an order has a subscription.
	 *
	 * @param int $order_id WC Order ID.
	 *
	 * @return bool
	 */
	public function order_has_subscription($order_id)
	{

		return function_exists('wcs_order_contains_subscription') && (wcs_order_contains_subscription($order_id) || wcs_order_contains_renewal($order_id));
	}
	/**
	 * Process a subscription renewal.
	 *
	 * @param float    $amount_to_charge Subscription payment amount.
	 * @param WC_Order $renewal_order Renewal Order.
	 */
	public function scheduled_subscription_payment($amount_to_charge, $renewal_order)
	{

		$response = $this->process_subscription_payment($renewal_order, $amount_to_charge);

		if (is_wp_error($response)) {

			$renewal_order->update_status('failed', sprintf(__('Rocketfuel Transaction Failed (%s)', 'rocketfuel-payment-gateway'), $response->get_error_message()));
		}
	}
	/**
	 * Process a trial subscription order with 0 total.
	 *
	 * @param int $order_id WC Order ID.
	 *
	 * @return array|void
	 */
	public function process_payment($order_id)
	{

		$order = wc_get_order($order_id);

		// Check for trial subscription order with 0 total.
		if ($this->order_has_subscription($order) && $order->get_total() == 0) {

			$order->payment_complete();

			$order->add_order_note(__('This subscription has a free trial, hence the 0 amount', 'rocketfuel-payment-gateway'));

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url($order),
			);
		} else {

			return parent::process_payment($order_id);
		}
	}

	/**
	 * Process a subscription renewal payment.
	 *
	 * @param WC_Order $order  Subscription renewal order.
	 * @param float    $amount Subscription payment amount.
	 *
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment($order, $amount)
	{
		return true; // do not allow yet
		$order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;

		$auth_code  = null;

		if ($auth_code) {

			$email = method_exists($order, 'get_billing_email') ? $order->get_billing_email() : $order->billing_email;

			$order_amount = $amount;

			// $paystack_url = $this->enpoint . '/charge/charge_authorization';

			// $headers = array(
			// 	'Content-Type'  => 'application/json',
			// 	'Authorization' => 'Bearer ' . $this->secret_key,
			// );

			// $metadata['custom_fields'] = $this->get_custom_fields($order_id);

			// $body = array(
			// 	'email'              => $email,
			// 	'amount'             => $order_amount,
			// 	'metadata'           => $metadata,
			// 	'authorization_code' => $auth_code,
			// );

			// $args = array(
			// 	'body'    => json_encode($body),
			// 	'headers' => $headers,
			// 	'timeout' => 60,
			// );

			// $request = wp_remote_post($paystack_url, $args);

			// if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {

			// 	// $paystack_response = json_decode(wp_remote_retrieve_body($request));

			// 	// if ('success' == $paystack_response->data->status) {

			// 	// 	$paystack_ref = $paystack_response->data->reference;

			// 	// 	$order->payment_complete($paystack_ref);

			// 	// 	$message = sprintf(__('Payment via Paystack successful (Transaction Reference: %s)', 'woo-paystack'), $paystack_ref);

			// 	// 	$order->add_order_note($message);

			// 	// 	if (parent::is_autocomplete_order_enabled($order)) {
			// 	// 		$order->update_status('completed');
			// 	// 	}

			// 	// 	return true;
			// 	// } else {

			// 	// 	$gateway_response = __('Paystack payment failed.', 'woo-paystack');

			// 	// 	if (isset($paystack_response->data->gateway_response) && !empty($paystack_response->data->gateway_response)) {
			// 	// 		$gateway_response = sprintf(__('Paystack payment failed. Reason: %s', 'woo-paystack'), $paystack_response->data->gateway_response);
			// 	// 	}

			// 	// 	return new WP_Error('paystack_error', $gateway_response);
			// 	// }
			// }
		}

		// return new WP_Error('rkfl_error', __('This subscription can&#39;t be renewed automatically. The customer will have to login to their account to renew their subscription', 'woo-paystack'));
	}
}
