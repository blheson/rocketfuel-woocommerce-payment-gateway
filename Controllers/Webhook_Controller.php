<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

/**
 * Webhook Controller
 */
class Webhook_Controller
{
	/**
	 * Payment method
	 *
	 * @param WP_REQUEST $request_data From wp request.
	 */
	public static function payment($request_data)
	{
		$body = $request_data->get_params();
		$data = $body['data'];

		$signature = $body['signature'];

		if (!self::verify_callback($data['data'], $signature)) {
			return false;
		}
		$order = wc_get_order($data['offerId']);
		if (!$order) {
			return false;
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
			file_put_contents(__DIR__."/log.json", "\n webhook".json_encode($body )."\n", FILE_APPEND);
			return true;
		}
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
	private static function get_gateway()
	{
		return new Rocketfuel_Gateway_Controller();
	}
	public static function get_callback_public_key()
	{
		$pub_key_path = dirname(__FILE__) . '/rf.pub';

		if (!file_exists($pub_key_path)) {
			return false;
		}
		return file_get_contents($pub_key_path);
	}
}
