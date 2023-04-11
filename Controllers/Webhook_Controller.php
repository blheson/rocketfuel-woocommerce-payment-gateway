<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Helpers\Common;

/**
 * Webhook Controller
 */
class Webhook_Controller
{

	public static function get_posts($parsed_args)
	{

		$get_posts = new \WP_Query($parsed_args);

		return $get_posts;
	}
	/**
	 * Payment method
	 *
	 * @param WP_REQUEST $request_data From wp request.
	 */
	public static function payment($request_data)
	{
		$body = wc_clean($request_data->get_params());
		$data = $body['data'];
	
		$signature = $body['signature'];

		if (!self::verify_callback($data['data'], $signature)) {
			return array(
				'error'   => 'true',
				'message' => 'Could not verify signature',
			);
		}

		$order = wc_get_order($data['offerId']);

		if (!$order) {

			$common_helper = self::get_helper();

			$query = $common_helper::get_posts(
				array(
					'post_type'   => 'shop_order',
					'post_status' => 'any',
					'meta_value'  => $data['offerId'],
				)
			);

			if (!$query->have_posts()) {
				$order = self::create_order_from_cache($data['offerId']);
	 
				self::swap_order_id($data['offerId'], $order->get_id());
	
			}
			if (!$order) {
				if (count($query->get_posts()) > 1) {
					return array(
						'error'   => 'true',
						'message' => 'Temp Offer Id is mapped to too many orders --> This must be fixed' . $data['offerId'],
					);
				}

				if (!isset($query->get_posts()[0]->ID)) {

					$order = self::create_order_from_cache($data['offerId']);
				} else {

					$order = wc_get_order($query->get_posts()[0]->ID);
				}
			}
		}

		if (isset($data['transactionId'])) {
			$order->set_transaction_id($data['transactionId']);
		}

		$status = (int) $data['paymentStatus'];
		if (0 === $status) {
			return true;
		}
		if (-1 === $status) {
			$order->update_status('wc-failed', 'Rocketfuel could not verify the payment');
			return true;
		}
		if (101 === $status) {
			$order->update_status('wc-partial-payment');
			return true;
		}
		if (1 === $status) {

			if (isset($data['isSubscription']) && $data['isSubscription'] === true) {

				$message = sprintf(__('Payment via Rocketfuel is successful (Transaction Reference: %s)', 'rocketfuel-payment-gateway'), isset($data['transactionId']) ? $data['transactionId'] : '');

				$order->add_order_note($message);

				if (class_exists('WC_Subscriptions_Manager')) {
					\WC_Subscriptions_Manager::process_subscription_payments_on_order($order);
				}
			}

			$default_status = self::get_gateway()->payment_complete_order_status;

			$default_status = $default_status ? $default_status : 'wc-completed';

			$order->update_status($default_status);

			$order->payment_complete();

			return true;
		}
	}
	/**
	 * 
	 */
	public static function swap_order_id($temp,$new_order){
	
				$gateway = new Rocketfuel_Gateway_Controller();
				$gateway->swap_order_id($temp, $new_order);
				unset( $gateway );
	}
	/**
	 * Undocumented function
	 *
	 * @param [type] $cache_key params
	 * @return void|any
	 */
	public static function create_order_from_cache($cache_key)
	{

		$cache_data = get_transient($cache_key);

		// $cache_data = [
		/**
		 *products = [['id',quanty]]
		 * shippings = [[id,title,amount]]
		 * billing_address
		 * shipping_address
		 * payment_method = > id,title
		 */
		// ]
		$order = wc_create_order();

		foreach ($cache_data['products'] as $value) {

			$order->add_product(wc_get_product($value['id']), $value['quantity']);
		}
		$order->calculate_totals();
		if (isset($cache_data['shippings'])) {
			foreach ($cache_data['shippings'] as $value) {

				$shipping = new \WC_Order_Item_Shipping();
				$shipping->set_method_title($value['title']);
				$shipping->set_method_id($value['id']); // set an existing Shipping method ID.
				$shipping->set_total($value['amount']); // optional.

				// add to order.

				$order->add_item($shipping);
			}
		}

		if (isset($cache_data['shipping_address'])) {
			$order->set_address($cache_data['shipping_address'], 'shipping');
		}

		if (isset($cache_data['billing_address'])) {
			$order->set_address($cache_data['billing_address'], 'billing');
		}
		if (isset($cache_data['customer_id'])) {
			$order->set_customer_id($cache_data['customer_id']);
		}

		if (version_compare(\WC_VERSION, '3.0', '<')) {
			// WooCommerce < 3.0.
			$payment_gateways = WC()->payment_gateways->payment_gateways();
			$order->set_payment_method($payment_gateways[$cache_data['payment_method']['id']]);
		} else {
			$order->set_payment_method($cache_data['payment_method']['id']);
			$order->set_payment_method_title($cache_data['payment_method']['title']);
		}
		// $order->set_status( $cache_data['order_status'] );
		$order->calculate_totals();
		$order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
		update_post_meta($order_id, 'rocketfuel_temp_orderid', $cache_key);
		$order->add_order_note('New order created with temporary order Id ->' . $cache_key);

		$order->save();
		return $order;
	}
	/**
	 * Check the callback
	 */
	public static function check_callback()
	{
		return rest_ensure_response(
			array(
				'callback_status' => 'ok',
			)
		);
	}

	/**
	 * Verify callback
	 *
	 * @param string $body body to verify.
	 * @param string $signature signature used for verification.
	 */
	public static function verify_callback($body, $signature)
	{
		$signature_buffer = base64_decode($signature);
		return (1 === openssl_verify($body, $signature_buffer, self::get_callback_public_key(), OPENSSL_ALGO_SHA256));
	}

	/**
	 * Get gateway instance
	 */
	private static function get_gateway()
	{
		return new Rocketfuel_Gateway_Controller();
	}
	public static function get_helper()
	{
		return new Common();
	}

	/**
	 * Retrieve public key
	 */
	public static function get_callback_public_key()
	{
		$pub_key_path = dirname(__FILE__) . '/rf.pub';

		if (!file_exists($pub_key_path)) {
			return false;
		}
		return file_get_contents($pub_key_path);
	}
}
