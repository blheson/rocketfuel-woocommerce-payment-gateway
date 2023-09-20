<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Helpers\Common;

/**
 * Webhook Controller
 */
class Webhook_Controller {


	/**
	 * Registers actions
	 */
	public static function register() {

		add_action(
			'rkfl_order_webhook_creation_hook',
			array(
				__CLASS__,
				'schedule_hook',
			)
		);
	}
	public static function get_posts( $parsed_args ) {

		$get_posts = new \WP_Query( $parsed_args );

		return $get_posts;
	}
	/**
	 *
	 */
	public static function schedule_hook( $data ) {

		$data = json_decode( $data, true );
		$common_helper = self::get_helper();

			$query = $common_helper::get_posts(
				array(
					'post_type'   => 'shop_order',
					'post_status' => 'any',
					'meta_value'  => $data['offerId'],
				)
			);

			if (  $query->have_posts() ) {

				return array(
					'error'   => 'true',
				);
			}

		$order = self::create_order_from_cache( $data['offerId'] , $data);

		if ( ! $order || (is_array($order) && isset($order['error'])) ) {

			return error_log( '[RKFL_ERROR] Could not create a manual order: '.json_encode($order) );
		}

		self::swap_order_id( $data['offerId'], $order->get_id() );

		return self::sort_order_status( $order, $data );

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
				'error'   => 'true',
				'message' => 'Could not verify signature. '. $data,
			);
		}
		$compare_data =  json_decode($data['data'], true);
		if($data['offerId'] !==$compare_data['offerId'] ){
			return array(
				'error'   => 'true',
				'message' => 'Data has been tampered with',
			);
		}

		$order = wc_get_order( $data['offerId'] );


		if ( ! $order ) {
			$common_helper = self::get_helper();

			$query = $common_helper::get_posts(
				array(
					'post_type'   => 'shop_order',
					'post_status' => 'any',
					'meta_value'  => $data['offerId'],
				)
			);

			// array('Rocketfuel_Gateway\Controllers\Webhook_Controller', 'schedule_hook')

			if ( ! $query->have_posts() ) {

				try {
					$is_scheduled_order = \get_transient( 'rkfl_scheduled_order_'.$data['offerId'] );
					if($is_scheduled_order){
						
						return array(
							'error'   => true,
							'message' => 'Order has been scheduled to be created',
						);
					}
					wp_schedule_single_event(
						time() + 120,
						'rkfl_order_webhook_creation_hook',
						$data
					);
					//Keep track of a scheduled hook
					\set_transient( 'rkfl_scheduled_order_'.$data['offerId'], $data['offerId'], self::get_helper()::days_in_secs( 3 ) );

					// self::schedule_hook( $data );
				} catch ( \Error $error ) {

					return array(
						'error'   => true,
						'message' => 'Schedule order not created',
					);
					// var_dump( $error, '$error' );
				}

				return array(
					'error'   => false,
					'message' => 'Schedule order creation',
				);
			}
			if ( ! $order ) {
				if ( count( $query->get_posts() ) > 1 ) {
					return array(
						'error'   => 'true',
						'message' => 'Temp Offer Id is mapped to too many orders --> This must be fixed' . $data['offerId'],
					);
				}

				if ( ! isset( $query->get_posts()[0]->ID ) ) {
					return array(
								'error'   => true,
								'message' => 'No order found after post found',
							);
				//$order = self::create_order_from_cache( $data['offerId'] );
				//self::swap_order_id( $data['offerId'], $order->get_id() );
				} else {

					$order = wc_get_order( $query->get_posts()[0]->ID );
				}
			}
		}

		if(!$order ){
			return array(
				'error'   => 'true',
				'message' => 'Could not find an order',
			);
		}

		return self::sort_order_status( $order, $data );
	}
	public static function sort_order_status( $order, $data ) {
		$compare_data =  json_decode($data['data'], true);

		if ( isset( $compare_data['transactionId'] ) ) {
			$order->set_transaction_id( $compare_data['transactionId'] );
		}

		$status = (int) $compare_data ['paymentStatus'];
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
	 *
	 */
	public static function swap_order_id( $temp, $new_order ) {

		$gateway = new Rocketfuel_Gateway_Controller();
		$gateway->swap_order_id( $temp, $new_order );
		unset( $gateway );
	}
	/**
	 * Undocumented function
	 *
	 * @param [type] $cache_key params
	 * @return void|any
	 */
	public static function create_order_from_cache( $cache_key, $data ) {

		$cache_data = get_transient( $cache_key );
		if ( ! $cache_data ) {
			return array(
				'error'   => true,
				'message' => 'no cache found ' . $cache_key,
			); 
		}
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
		$signature_data = json_decode($data['data'],true);

		if( (float)$cache_data['total'] !== (float)$signature_data['amount']){
			return array(
				'error'   => true,
				'message' => 'error ',
			); 
		}
		foreach ( $cache_data['products'] as $value ) {
			$product_id = $value['type'] === Cart_Handler_Controller::$product_type_variant ? $value['variant_id']: $value['id'];
		
			$product = wc_get_product( $product_id );
			
			$order->add_product( $product, $value['quantity'] );
		}
		// $order->calculate_totals();
		if ( isset( $cache_data['shippings'] ) ) {
			foreach ( $cache_data['shippings'] as $value ) {

				$shipping = new \WC_Order_Item_Shipping();
				$shipping->set_method_title( $value['title'] );
				$shipping->set_method_id( $value['id'] ); // set an existing Shipping method ID.
				$shipping->set_total( $value['amount'] ); // optional.

				// add to order.

				$order->add_item( $shipping );
			}
		}

		if ( isset( $cache_data['shipping_address'] ) ) {
			$order->set_address( $cache_data['shipping_address'], 'shipping' );
		}

		if ( isset( $cache_data['billing_address'] ) ) {
			$order->set_address( $cache_data['billing_address'], 'billing' );
		}
		if ( isset( $cache_data['customer_id'] ) ) {
			$order->set_customer_id( $cache_data['customer_id'] );
		}

		if ( version_compare( \WC_VERSION, '3.0', '<' ) ) {
			// WooCommerce < 3.0.
			$payment_gateways = WC()->payment_gateways->payment_gateways();
			$order->set_payment_method( $payment_gateways[ $cache_data['payment_method']['id'] ] );
		} else {
			$order->set_payment_method( $cache_data['payment_method']['id'] );
			$order->set_payment_method_title( $cache_data['payment_method']['title'] );
		}
		// $order->set_status( $cache_data['order_status'] );
		// $order->calculate_totals();
		$order->set_total($signature_data['amount']);
		$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
		update_post_meta( $order_id, 'rocketfuel_temp_orderid', $cache_key );
		$order->add_order_note( 'New order created with temporary order Id ->' . $cache_key );

		$order->save();
		delete_transient( $cache_key );

		return $order;
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
	public static function get_helper() {
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
		return file_get_contents( $pub_key_path );
	}
}
