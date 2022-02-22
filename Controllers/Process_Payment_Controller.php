<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

class Process_Payment_Controller{
	/**
	 * Process data to get uuid
	 *
	 * @param array $data - Data from plugin.
	 */
	public static function process_payment( $data ){

		$response = self::auth( $data );

		if ( is_wp_error( $response ) ) {
			return rest_ensure_response( $response );
		}
		
		$response_code = wp_remote_retrieve_response_code( $response );
	
		$response_body = wp_remote_retrieve_body( $response );

		$result = json_decode( $response_body );
		if( $response_code != '200'){
			// wc_add_notice( __( 'Authorization cannot be completed', 'rocketfuel-payment-gateway' ), 'error' );
			return false; 
		}
	

		$charge_response = self::createCharge( $result->result->access, $data );

		$charge_response_code = wp_remote_retrieve_response_code( $charge_response );

		$wp_remote_retrieve_body = wp_remote_retrieve_body( $charge_response);
		
	

		if( $charge_response_code != '200' ){
			// wc_add_notice( __( 'Could not establish an order', 'rocketfuel-payment-gateway' ), 'error' );
			return false;
		}

		return $wp_remote_retrieve_body;
	}
	/**
	 * Process authentication
	 * @param array $data
	 */
	public static function auth($data)
	{
		$body = wp_json_encode($data['cred']);
		$args = array(
			'timeout'	 => 200,
			'headers' => array('Content-Type' => 'application/json'),
			'body' => $body
		);
	
	$response = wp_remote_post($data['endpoint'] . '/auth/login', $args);
	return $response;
    }
	/**
	* Get UUID of the customer
	* @param array $data
	*/
    public static function createCharge($accessToken, $data)
    {    
	$body = wp_json_encode($data['body']);
	$args = array(
		'timeout'	=> 45,
		'headers' => array('authorization' => "Bearer  $accessToken", 'Content-Type' => 'application/json'),
		'body' => $body
	);
	  
	$response = wp_remote_post($data['endpoint'].'/hosted-page', $args);


	return $response;
    }
}
