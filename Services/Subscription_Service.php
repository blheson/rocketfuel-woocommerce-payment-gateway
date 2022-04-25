<?php

namespace Rocketfuel_Gateway\Services;

use Rocketfuel_Gateway\Plugin;

class Subscription_Service
{

	/**
	 * Get UUID of the customer
	 * @param array $data
	 */
	public static function cancel_subscription($data)
	{


		$body = wp_json_encode(array(
			'merchantId' => $data['merchant_id'],
			'merchantAuth' => $data['merchant_auth'],
			'subscriptionId' => $data['subscription_id']
		));

		$args = array(
			'timeout'	=> 45,
			'headers' => array('Content-Type' => 'application/json'),
			'body' => $body
		);

		$response = wp_remote_post($data['endpoint'] . '/subscription/cancel', $args);

		return $response;
	}
	/**
	 * Get UUID of the customer
	 * @param array $data
	 */
	public static function debit_shopper_for_subscription($data,$endpoint)
	{


		// $body = wp_json_encode(array(
		// 	'merchantId' => $data['merchant_id'],
		// 	'merchantAuth' => $data['merchant_auth']
		// ));

		$body = wp_json_encode(
			$data
		);

		$args = array(
			'timeout'	=> 45,
			'headers' => array('Content-Type' => 'application/json'),
			'body' => $body
		);

		$response = wp_remote_post($endpoint . '/subscription/debit', $args);

		return $response;
	}
}
