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
}
