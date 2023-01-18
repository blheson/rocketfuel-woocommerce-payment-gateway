<?php
namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Helpers\Common;

/**
 * Webhook Controller
 */
class Webhook_Controller {
	public static function get_posts( $parsed_args ){

        $get_posts = new \WP_Query($parsed_args );
 
        return $get_posts;
        $query = self::get_posts( array(
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'meta_value' => $_rkfl_partial_payment_cache['temporary_order_id'],
        ));
    }
	/**
	 * Payment method
	 *
	 * @param WP_REQUEST $request_data From wp request.
	 */
	public static function payment( $request_data ) {
		$body = wc_clean( $request_data->get_params() );
		$data = $body['data'];

		$signature = $body['signature'];

		if ( ! self::verify_callback( $data['data'], $signature ) ) {
			return array(
				'error'=>'true',
				'message'=>'Could not verify signature'
			);
		}
		$order = wc_get_order( $data['offerId'] );
		
		if ( ! $order ) {

			$common_helper = self::get_helper();
		
			$query = $common_helper::get_posts( 
				array(
				'post_type' => 'shop_order',
				'post_status' => 'any',
				'meta_value' => $data['offerId'],
				)
			);
			if(!$query->have_posts()){
				return array(
					'error'=>'true',
					'message'=>'No order was found for orderId '.$data['offerId']
				);
			}
			if(count( $query->get_posts() ) > 1 ){
				return array(
					'error'=>'true',
					'message'=>'Temp Offer Id is mapped to too many orders --> This must be fixed'.$data['offerId']
				);
			}
			 
			if(!isset($query->get_posts()[0]->ID)){
				return array(
					'error'=>'true',
					'message'=>'No order ID found for this temporary order Id'.$data['offerId']
				);
			}

			$order = wc_get_order( $query->get_posts()[0]->ID );
			
		}

		if ( isset( $data['transactionId'] ) ) {
			$order->set_transaction_id( $data['transactionId'] );
		}

		$status = (int) $data['paymentStatus'];
		if ( 0 === $status ) {
			return true;
		}
		if ( -1 === $status ) {
			$order->update_status( 'wc-failed', 'Rocketfuel could not verify the payment' );
			return true;
		}
		if ( 101 === $status ) {
			$order->update_status( 'wc-partial-payment' );
			return true;
		}
		if ( 1 === $status ) {

			if ( isset( $data['isSubscription'] ) && $data['isSubscription'] === true ) {

				$message = sprintf( __( 'Payment via Rocketfuel is successful (Transaction Reference: %s)', 'rocketfuel-payment-gateway' ), isset( $data['transactionId'] ) ? $data['transactionId'] : '' );

				$order->add_order_note( $message );

				if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
					\WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
				}
			}

			$default_status = self::get_gateway()->payment_complete_order_status;

			$default_status = $default_status ? $default_status : 'wc-completed';

			$order->update_status( $default_status );

			$order->payment_complete();

			return true;
		}
	}

	/**
	 * Check the callback
	 */
	public static function check_callback() {
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
	public static function verify_callback( $body, $signature ) {
		$signature_buffer = base64_decode( $signature );
		return ( 1 === openssl_verify( $body, $signature_buffer, self::get_callback_public_key(), OPENSSL_ALGO_SHA256 ) );
	}

	/**
	 * Get gateway instance
	 */
	private static function get_gateway() {
		return new Rocketfuel_Gateway_Controller();
	}
	public static function get_helper(){
		
		return new Common();
	}

	/**
	 * Retrieve public key
	 */
	public static function get_callback_public_key() {
		$pub_key_path = dirname( __FILE__ ) . '/rf.pub';

		if ( ! file_exists( $pub_key_path ) ) {
			return false;
		}
		return file_get_contents($pub_key_path);
	}
}
