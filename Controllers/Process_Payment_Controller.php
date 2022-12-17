<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;
use stdClass;

class Process_Payment_Controller{

	private static function create_error( $message, $data ){

			$errorObj = new stdClass();
	
			$errorObj->error = true;
			$errorObj->message = $message;
			$errorObj->data = $data;
			return $errorObj;
	}
	/**
	 * Process data to get uuid
	 *
	 * @param array $data - Data from plugin.
	 */
	public static function process_payment( $data ) {

		$response = self::auth( $data );

		if ( is_wp_error( $response ) ) {
			return rest_ensure_response( $response );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		$response_body = wp_remote_retrieve_body( $response );

		$result = json_decode( $response_body );

		if ( $response_code != '200' ) {
			$error_message = 'Authorization cannot be completed';

			wc_add_notice( __($error_message, 'rocketfuel-payment-gateway' ), 'error' );

			return self::create_error($error_message,$response_body );
		}

		$charge_response = self::create_charge( $result->result->access, $data );
		$charge_response_code = wp_remote_retrieve_response_code( $charge_response );
		$wp_remote_retrieve_body = wp_remote_retrieve_body( $charge_response );
		
		if ( $charge_response_code != '200' ) {
			$error_message = 'Could not establish an order';
			wc_add_notice( __( $error_message, 'rocketfuel-payment-gateway' ), 'error' );

			return self::create_error($error_message,$wp_remote_retrieve_body);

			 
		}

		$create_charge = json_decode( $wp_remote_retrieve_body );

		$create_charge->access_token = $result->result->access;

		return $create_charge;

	}
	/**
	 * Process authentication
	 * 
	 * @param array $data
	 */
	public static function auth( $data ) {
		$body = wp_json_encode( $data['cred'] );
		$args = array(
			'timeout'	 => 200,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body' => $body
		);

		$response = wp_remote_post( $data['endpoint'] . '/auth/login', $args );
		return $response;
	}
	/**
	 * Get UUID of the customer
	 * 
	 * @param array $data
	 */
	public static function create_charge( $access_token, $data ) {
		$body = wp_json_encode( $data['body'] );
		$args = array(
			'timeout'	=> 45,
			'headers' => array( 'authorization' => "Bearer  $access_token",
			'Content-Type' => 'application/json'
		),
			'body' => $body
		);
		$response = wp_remote_post( $data['endpoint'] . '/hosted-page', $args );
		return $response;
	}
}
