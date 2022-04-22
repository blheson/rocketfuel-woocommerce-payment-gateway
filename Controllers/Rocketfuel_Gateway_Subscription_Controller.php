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

		$order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
		
		$subscriptions = wcs_get_subscriptions_for_order($order_id);
		
        $subscription_count           = count( $subscriptions );

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

							'subscriptionId'=>$temporary_order_id . '-' . $_product_id,

							'amount'=>$product->get_price() *$item->get_quantity(),

							'currency'=>$currency 
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

				"orderId" => (string)$order_id,

				'items'=>$subscriptionData,

				'endpoint' => $this->endpoint
			);
			file_put_contents(__DIR__.'/log.json',"\n".json_encode($payload)."\n",FILE_APPEND);

            $response = Subscription_Service::debit_shopper_for_subscription($payload);

            $order->payment_complete();

            return true;

        } catch (\Throwable $th) {
			//throw $th;
		}



		return new \WP_Error('rkfl_error', __('This subscription can&#39;t be renewed automatically. The customer will have to login to their account to renew their subscription', 'woo-paystack'));
	}
}
