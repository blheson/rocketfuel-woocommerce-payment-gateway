<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

class Metabox_Controller{

	public static function register(){

		// if (is_admin()) {
		
		// 	add_meta_box('rkfl_cancel_subscription_button', 'Cancel Subcription', );
		
		// }
	
	}

	/**
	 * Render cancel Subscription button
	 * @param array $data
	 */
	public static function cancel_subscription_button($accessToken, $data){

		global $post;
		
		$body = wp_json_encode($data['body']);

		$args = array(
			'timeout'	=> 45,
			'headers' => array('authorization' => "Bearer  $accessToken", 'Content-Type' => 'application/json'),
			'body' => $body
		);

		$response = wp_remote_post($data['endpoint'] . '/hosted-page', $args);

		return $response;
	}
	
}
