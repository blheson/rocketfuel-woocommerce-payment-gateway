<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Services\Subscription_Service;

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

			add_action('woocommerce_scheduled_subscription_payment', array($this, 'scheduled_subscription_payment_single'), 10, 2);
		}
	}
	public function scheduled_subscription_payment_single($subscription_id)
	{
		file_put_contents(__DIR__ . '/sub_single.json', '$renewal_order ' . json_encode($subscription_id), FILE_APPEND);
		$subscription = wcs_get_subscription($subscription_id);

		$renewal_order = $subscription->get_last_order('all');

		file_put_contents(__DIR__ . '/sub_single.json', '$renewal_order ' . json_encode($renewal_order), FILE_APPEND);

		$payment_method = $renewal_order->get_payment_method();
		file_put_contents(__DIR__ . '/sub_single.json', '$payment_method ' . json_encode($payment_method ), FILE_APPEND);

		$total_amount = $renewal_order->get_total();

		// Make sure gateways are setup
		WC()->payment_gateways();

		file_put_contents(__DIR__ . '/sub_single.json', 'it is called ' . json_encode($subscription_id), FILE_APPEND);

		file_put_contents(__DIR__ . '/sub_single.json', '$subscription->is_manual() ' . json_encode($subscription->is_manual()), FILE_APPEND);


		$response = $this->process_single_subscription_payment($subscription, $renewal_order, $total_amount);

		return;
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
		file_put_contents(__DIR__ . '/sub.json', 'it is called', FILE_APPEND);
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
			$temporary_order_id = get_post_meta($order_id, 'rocketfuel_temp_orderid', true);

			$this->swap_order_id($temporary_order_id, $order_id);

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

		$order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;

		$subscriptions = wcs_get_subscriptions_for_order($order_id);

		$subscription_count           = count($subscriptions);

		$currency = $order->get_order_currency();


		try {
			$subscriptionData = array();

			$temporary_order_id = get_post_meta($order_id, 'rocketfuel_temp_orderid', true);

			foreach ($subscriptions as $subscription) {

				$sub_items = $subscription->get_items();

				// Loop through order items

				foreach ($sub_items as $item_id => $item) {

					$product = $item->get_product();

					// Or to get the simple subscription or the variation subscription product ID
					$_product_id = $product->get_id();

					array_push(
						$subscriptionData,
						array(

							'subscriptionId' => $temporary_order_id . '-' . $_product_id,

							'amount' => $product->get_price() * $item->get_quantity(),

							'currency' => $currency
						)
					);
				}
			}

			$payload = array(
				'merchantId' => base64_encode($this->merchant_id),

				'merchantAuth' => $this->get_encrypted(
					json_encode(array('merchantId' => $this->merchant_id)),
					false
				),

				"orderId" => (string)$order_id.'-'.md5(time()),

				'items' => $subscriptionData

			);
			

			$response = Subscription_Service::debit_shopper_for_subscription(
				$payload,
				$this->endpoint
			);

			file_put_contents(__DIR__ . '/sub.json', "\n" . '##############################' . json_encode($response) . '##############################' . "\n", FILE_APPEND);
			$order->payment_complete();

			return true;
		} catch (\Throwable $th) {
			//throw $th;
		}



		return new WP_Error('rkfl_error', __('This subscription can&#39;t be renewed automatically. The customer will have to login to their account to renew their subscription', 'rocketfuel'));
	}
	/**
	 * Process a subscription renewal payment.
	 *
	 * @param WC_Order $order  Subscription renewal order.
	 * @param float    $amount Subscription payment amount.
	 *
	 * @return bool|WP_Error
	 */
	public function process_single_subscription_payment($subscription, $order, $amount)
	{

		$order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;

		$currency = $order->get_order_currency();

		try {
			$subscriptionData = array();

			$temporary_order_id = get_post_meta($order_id, 'rocketfuel_temp_orderid', true);


			$sub_items = $subscription->get_items();

			// Loop through order items

			foreach ($sub_items as $item_id => $item) {

				$product = $item->get_product();

				// Or to get the simple subscription or the variation subscription product ID
				$_product_id = $product->get_id();

				array_push(
					$subscriptionData,
					array(

						'subscriptionId' => $temporary_order_id . '-' . $_product_id,

						'amount' => $product->get_price() * $item->get_quantity(),

						'currency' => $currency
					)
				);
			}

			$payload = array(
				'merchantId' => base64_encode($this->merchant_id),

				'merchantAuth' => $this->get_encrypted(
					json_encode(array('merchantId' => $this->merchant_id)),
					false
				),

				"orderId" => (string)$order_id.'-'.md5(time()),

				'items' => $subscriptionData
			);

			file_put_contents(__DIR__ . '/sub_single.json', "\n" . json_encode($payload) . "\n", FILE_APPEND);

			$response = Subscription_Service::debit_shopper_for_subscription($payload, $this->endpoint);

			file_put_contents(__DIR__ . '/sub_single.json', "\n" . '##############################' . json_encode($response) . '##############################' . "\n", FILE_APPEND);
if($response->statusCode === '400'){
	return new WP_Error( 'rocketfuel_error', $response );

}else{
	$order->payment_complete();
}
			

			return true;
		} catch (\Throwable $th) {
			//throw $th;
		}



		return new WP_Error('rkfl_error', __('This subscription can&#39;t be renewed automatically. The customer will have to login to their account to renew their subscription', 'rocketfuel'));
	}
}
