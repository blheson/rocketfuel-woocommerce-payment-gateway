<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;
/**
 *
 */
class Cart_Handler_Controller {


	/**
	 * Registers actions
	 */
	public static function register() {

		add_action(
			'wc_ajax_wc_rkfl_start_checkout',
			array(
				__CLASS__,
				'rocketfuel_process_checkout',
			)
		);
	}
	public static function sort_shipping_address_raw() {

		$phone = method_exists( WC()->customer, 'get_shipping_phone' ) ?
			WC()->customer->get_shipping_phone() : ( method_exists( WC()->customer, 'get_billing_phone' ) ? WC()->customer->get_billing_phone() : false );

		// if (!$phone) {
		// return null;
		// }

		$zipcode = method_exists( WC()->customer, 'get_shipping_postcode' ) ?
			WC()->customer->get_shipping_postcode() : false;

		$email = method_exists( WC()->customer, 'get_email' ) ?
			WC()->customer->get_email() : false;

		$country_code = method_exists( WC()->customer, 'get_shipping_country' ) ?
			WC()->customer->get_shipping_country() : '';

		$country = ! ! $country_code ? WC()->countries->countries[ $country_code ] : '';
		// if (!$country) {
		// return null;
		// }
		$state_code = method_exists( WC()->customer, 'get_shipping_state' ) ?
			WC()->customer->get_shipping_state() : '';

		$states = $state_code ? WC()->countries->get_states( $country_code ) : array();

		$state = ! empty( $states[ $state_code ] ) ? $states[ $state_code ] : $state_code;
		// if (!$state) {
		// return null;
		// }
		$address1 = method_exists( WC()->customer, 'get_shipping_address' ) ?
			WC()->customer->get_shipping_address() : '';

		// if (!$address1) {
		// return null;
		// }

		$city = method_exists( WC()->customer, 'get_shipping_city' ) ?
			WC()->customer->get_shipping_city() : '';

		// if (!$city) {
		// return null;
		// }
		$firstname = isset( $_GET['firstname'] ) ?
			sanitize_text_field( wp_unslash( $_GET['firstname'] ) ) : '';

		$lastname = isset( $_GET['lastname'] ) ?
			sanitize_text_field( wp_unslash( $_GET['lastname'] ) ) : '';

		return array(
			'phoneNo'   => $phone ? $phone : ( method_exists( WC()->customer, 'get_billing_phone' ) ?
				WC()->customer->get_billing_phone() : '' ),
			'email'     => $email ? $email : ( method_exists( WC()->customer, 'get_billing_email' ) ?
				WC()->customer->get_billing_email() : '' ),
			'address1'  => $address1,
			'address2'  => method_exists( WC()->customer, 'get_shipping_address_2' ) ?
				WC()->customer->get_shipping_address_2() : '',
			'state'     => $state,
			'city'      => $city,
			'zipcode'   => $zipcode,
			'country'   => $country,
			'landmark'  => '',
			'firstname' => method_exists( WC()->customer, 'get_shipping_first_name' ) ?
				WC()->customer->get_shipping_first_name() : $firstname,
			'lastname'  => method_exists( WC()->customer, 'get_shipping_last_name' ) ?
				WC()->customer->get_shipping_last_name() : $lastname,
		);
	}
	public static function sort_billing_address_raw() {

		$phone = method_exists( WC()->customer, 'get_billing_phone' ) ?
			WC()->customer->get_billing_phone() : ( method_exists( WC()->customer, 'get_shipping_phone' ) ? WC()->customer->get_shipping_phone() : false );

		// if (!$phone) {
		// return null;
		// }

		$zipcode = method_exists( WC()->customer, 'get_billing_postcode' ) ?
			WC()->customer->get_billing_postcode() : false;

		$email = method_exists( WC()->customer, 'get_email' ) ?
			WC()->customer->get_email() : false;

		$country_code = method_exists( WC()->customer, 'get_billing_country' ) ?
			WC()->customer->get_billing_country() : '';

		$country = ! ! $country_code ? WC()->countries->countries[ $country_code ] : '';
		// if (!$country) {
		// return null;
		// }
		$state_code = method_exists( WC()->customer, 'get_billing_state' ) ?
			WC()->customer->get_billing_state() : '';

		$states = $state_code ? WC()->countries->get_states( $country_code ) : array();

		$state = ! empty( $states[ $state_code ] ) ? $states[ $state_code ] : $state_code;
		// if (!$state) {
		// return null;
		// }
		$address1 = method_exists( WC()->customer, 'get_billing_address' ) ?
			WC()->customer->get_billing_address() : '';

		// if (!$address1) {
		// return null;
		// }

		$city = method_exists( WC()->customer, 'get_billing_city' ) ?
			WC()->customer->get_billing_city() : '';

		// if (!$city) {
		// return null;
		// }
		$firstname = isset( $_GET['firstname'] ) ?
			sanitize_text_field( wp_unslash( $_GET['firstname'] ) ) : '';

		$lastname = isset( $_GET['lastname'] ) ?
			sanitize_text_field( wp_unslash( $_GET['lastname'] ) ) : '';

		return array(
			'phoneNo'   => $phone ? $phone : ( method_exists( WC()->customer, 'get_billing_phone' ) ?
				WC()->customer->get_billing_phone() : '' ),
			'email'     => $email ? $email : ( method_exists( WC()->customer, 'get_billing_email' ) ?
				WC()->customer->get_billing_email() : '' ),
			'address1'  => $address1,
			'address2'  => method_exists( WC()->customer, 'get_shipping_address_2' ) ?
				WC()->customer->get_shipping_address_2() : '',
			'state'     => $state,
			'city'      => $city,
			'zipcode'   => $zipcode,
			'country'   => $country,
			'landmark'  => '',
			'firstname' => method_exists( WC()->customer, 'get_shipping_first_name' ) ?
				WC()->customer->get_shipping_first_name() : $firstname,
			'lastname'  => method_exists( WC()->customer, 'get_shipping_last_name' ) ?
				WC()->customer->get_shipping_last_name() : $lastname,
		);
	}
	public static function sort_shipping_address() {

		$shipping_raw = self::sort_shipping_address_raw();

		if ( ! $shipping_raw['phoneNo'] ) {
			return null;
		}

		if ( ! $shipping_raw['country'] ) {
			return null;
		}

		if ( ! $shipping_raw['state'] ) {
			return null;
		}

		if ( ! $shipping_raw['address1'] ) {
			return null;
		}

		if ( ! $shipping_raw['city'] ) {
			return null;
		}

		return $shipping_raw;
		return array(
			'phoneNo'   => $phone ? $phone : ( method_exists( WC()->customer, 'get_billing_phone' ) ?
				WC()->customer->get_billing_phone() : '' ),
			'email'     => $email ? $email : ( method_exists( WC()->customer, 'get_billing_email' ) ?
				WC()->customer->get_billing_email() : '' ),
			'address1'  => $address1,
			'address2'  => method_exists( WC()->customer, 'get_shipping_address_2' ) ?
				WC()->customer->get_shipping_address_2() : '',
			'state'     => $state,
			'city'      => $city,
			'zipcode'   => $zipcode,
			'country'   => $country,
			'landmark'  => '',
			'firstname' => method_exists( WC()->customer, 'get_shipping_first_name' ) ?
				WC()->customer->get_shipping_first_name() : $firstname,
			'lastname'  => method_exists( WC()->customer, 'get_shipping_last_name' ) ?
				WC()->customer->get_shipping_last_name() : $lastname,
		);
	}
	public static function sort_billing_address() {
		$billing_raw = self::sort_billing_address_raw();

		if ( ! $billing_raw['phoneNo'] ) {
			return null;
		}

		if ( ! $billing_raw['country'] ) {
			return null;
		}
		if ( ! $billing_raw['state'] ) {
			return null;
		}

		if ( ! $billing_raw['address1'] ) {
			return null;
		}

		if ( ! $billing_raw['city'] ) {
			return null;
		}

		return $billing_raw;
	}
	public static function get_posts( $parsed_args ) {

		$get_posts = new \WP_Query( $parsed_args );

		return $get_posts;
	}

	public static function compare_cart_partial_tx( $external_tx_info ) {

		$external_cart_info = $external_tx_info->check;

		if ( ! is_array( $external_cart_info ) ) {
			return false;
		}

		if ( $external_tx_info->nativeAmount < WC()->cart->total ) {

		
			return false;
		}

		if ( count( $external_cart_info ) !== count( WC()->cart->get_cart() ) ) {


			return false;
		}

		$external_cart_id_array = array_map(
			function ( $element ) {

				return $element->id;
			},
			$external_cart_info
		);

		$flag_incompatible_cart_product = false;

		foreach ( WC()->cart->get_cart() as  $cart_item ) {

			$is_product_present = array_search( (string) $cart_item['product_id'], $external_cart_id_array );

			if ( $is_product_present === false ) {

			 
				$flag_incompatible_cart_product = true;
			}
		}

		if ( $flag_incompatible_cart_product === true ) {

			return false;
		}

		return true;
	}
	public static function days_in_secs( $days ) {
		return 60 * 60 * 24 * (int) $days;
	}
	public static function get_cart_products( $cart ) {
		$cache = array();
		foreach ( $cart as $cart_item ) {
			$cache[] = array(
				'id'       => (string) $cart_item['product_id'],
				'quantity' => (string) $cart_item['quantity'],
			);
		}
		return $cache;
	}
	public static function get_cart_shippings( $cart ) {

		return array(
			array(
				'id'     => 'Shipping',
				'amount' => WC()->cart->get_shipping_total(),
			),
		);
	}
	public static function get_billing_address_for_transcient() {
		$sorted_billing = self::sort_billing_address_raw();

		return array(
			'first_name' => $sorted_billing['firstname'],
			'last_name'  => $sorted_billing['lastname'],
			'email'      => $sorted_billing['email'],
			'phone'      => $sorted_billing['phoneNo'],
			'address_1'  => $sorted_billing['address1'],
			'address_2'  => $sorted_billing['address2'],
			'city'       => $sorted_billing['city'],
			'state'      => $sorted_billing['state'],
			'postcode'   => $sorted_billing['zipcode'],
			'country'    => $sorted_billing['country'],
		);
	}
	public static function get_shipping_address_for_transcient() {
		$sorted_shipping = self::sort_shipping_address_raw();

		$address = array(
			'first_name' => $sorted_shipping['firstname'],
			'last_name'  => $sorted_shipping['lastname'],
			'email'      => $sorted_shipping['email'],
			'phone'      => $sorted_shipping['phoneNo'],
			'address_1'  => $sorted_shipping['address1'],
			'address_2'  => $sorted_shipping['address2'],
			'city'       => $sorted_shipping['city'],
			'state'      => $sorted_shipping['state'],
			'postcode'   => $sorted_shipping['zipcode'],
			'country'    => $sorted_shipping['country'],
		);
		return $address;
	}
	public static function process_user_data() {

		$temporary_order_id = md5( microtime() );

		// $cache_data = [
		/**
		 *products = [['id',quanty]]
		 * shippings = [[id,title,amount]]
		 * billing_address
		 * shipping_address
		 * payment_method = > id,title
		 */
		// ]
		$gateway = new Rocketfuel_Gateway_Controller();

		$transient_value = array(
			'merchant_id'      => $gateway->merchant_id,
			'products'         => self::get_cart_products( WC()->cart->get_cart() ),
			'shippings'        => self::get_cart_shippings( WC()->cart->get_cart() ),
			'billing_address'  => self::get_billing_address_for_transcient(),
			'shipping_address' => self::get_shipping_address_for_transcient(),
			'payment_method'   => array(
				'id'    => $gateway->id,
				'title' => $gateway->method_title,
			),
			'customer_id'      => WC()->customer->get_id(),
		);
	 
		\set_transient( $temporary_order_id, $transient_value, self::days_in_secs( 2 ) );

		$email = isset( $_POST['rkfl_checkout_email'] ) ? sanitize_email( wp_unslash( $_POST['rkfl_checkout_email'] ) ) : '';

		$partial_payment_cache_key = 'rkfl_partial_payment_cache_' . $email;

		$_rkfl_partial_payment_cache = get_option( $partial_payment_cache_key );

	 

		$merchant_cred = array(
			'email'    => $gateway->email,
			'password' => $gateway->password,
		);

		$firstname = isset( $_POST['rkfl_checkout_firstname'] ) ? sanitize_text_field( wp_unslash( $_POST['rkfl_checkout_firstname'] ) ) : '';

		$lastname = isset( $_POST['rkfl_checkout_lastname'] ) ? sanitize_text_field( wp_unslash( $_POST['rkfl_checkout_lastname'] ) ) : '';

		$shipping_address = self::sort_shipping_address();

		$to_encrypt = array(
			'email'     => isset( $email ) ? $email : ( ! is_null( $shipping_address ) ? $shipping_address['email'] : '' ),

			'firstName' => isset( $firstname ) ? $firstname : ( ! is_null( $shipping_address ) ? $shipping_address['firstname'] : '' ),

			'lastName'  => isset( $lastname ) ? $lastname : ( ! is_null( $shipping_address ) ? $shipping_address['lastname'] : '' ),
		);

		$encrypted_req = $gateway->get_encrypted( json_encode( $to_encrypt ) );

		if (
			( $_POST['rkfl_checkout_partial_tx_check'] == 'true' ) &&
			$_rkfl_partial_payment_cache &&
			isset( $_rkfl_partial_payment_cache['temporary_order_id'] )
		) {

			$query = self::get_posts(
				array(
					'post_type'   => 'shop_order',
					'post_status' => 'any',
					'meta_value'  => $_rkfl_partial_payment_cache['temporary_order_id'],
				)
			);
		 

			if ( count( $query->posts ) > 0 ) {
				// if order exists

				delete_option( 'rkfl_partial_payment_cache_' . $email );
			} else {

				$auth_pass = Process_Payment_Controller::auth(
					array(
						'cred'     => $merchant_cred,
						'endpoint' => $gateway->endpoint,
					)
				);

				if ( is_wp_error( $auth_pass ) ) {
					wp_send_json_success( rest_ensure_response( $auth_pass ) );
				}

				$response_code = wp_remote_retrieve_response_code( $auth_pass );

				$response_body = wp_remote_retrieve_body( $auth_pass );

				$result = json_decode( $response_body );

				if ( $response_code != '200' ) {
					$error_message = 'Authorization cannot be completed';

					wc_add_notice( __( $error_message, 'rocketfuel-payment-gateway' ), 'error' );

					return wp_send_json_error(
						array(
							'error'    => true,
							'messages' => array( $error_message ),
						)
					);
				}
				$rkfl_access_token = $result->result->access;

				$args = array(
					'timeout' => 200,
					'headers' => array(
						'Content-Type'  => 'application/json',
						'authorization' => 'Bearer ' . $rkfl_access_token,
					),
				);

				$url = $gateway->get_configured_endpoint() . '/purchase/transaction/partials/' . $gateway->get_merchant_id() . '?offerId=' . $_rkfl_partial_payment_cache['temporary_order_id'] . '&hostedPageId=' . $_rkfl_partial_payment_cache['uuid'];
			 
				$result = wp_remote_get( $url, $args );

				$response_code = wp_remote_retrieve_response_code( $result );

				if ( (int) $response_code === 200 ) {

					// $error_message = 'Could not retrieve Partial Payment';

					// wc_add_notice( __( $error_message, 'rocketfuel-payment-gateway' ), 'error' );

					$response_string = wp_remote_retrieve_body( $result );

					$response_body = json_decode( $response_string );

					if ( isset( $response_body->result->tx ) && ! is_null( $response_body->result->tx ) && (int) $response_body->result->tx->status === 101 && (int) $response_body->result->paymentLinkStatus === 1 && self::compare_cart_partial_tx( $response_body->result->tx ) ) {

						wp_send_json_success(
							array(
								'is_partial'         => true,
								'encrypted_req'      => $encrypted_req,
								'temporary_order_id' => $temporary_order_id,
								'uuid'               => array(
									'access_token' => $rkfl_access_token,
									'result'       => array(
										'uuid' => $response_body->result->tx->hostedPageId,
									),
								),
							)
						);
					}
				}
			}
		}

		$cart = $gateway->sort_cart( WC()->cart->get_cart(), $temporary_order_id );

		$data = array(
			'cred'     => $merchant_cred,
			'endpoint' => $gateway->endpoint,
			'body'     => array(
				'amount'          => WC()->cart->total,
				'cart'            => $cart,
				'merchant_id'     => $gateway->merchant_id,
				'shippingAddress' => $shipping_address,
				'currency'        => get_woocommerce_currency( 'USD' ),
				'order'           => (string) $temporary_order_id,
				'redirectUrl'     => '',
			),
		);

		unset( $gateway );

		$error_message = 'Payment cannot be completed';

		try {
			$payment_response = Process_Payment_Controller::process_payment( $data );
			if ( is_wp_error( $payment_response ) ) {
				return rest_ensure_response( $payment_response );
			}

			if ( ! $payment_response ) {
				return wp_send_json_error(
					array(
						'error'    => true,
						'messages' => array( $error_message ),
					)
				);
			}

			if ( ( isset( $payment_response->error ) && $payment_response->error === true ) ) {
			 
				try {
					wp_send_json_error(
						array(
							'error'    => true,
							'messages' => array( isset( $payment_response->message ) ? $payment_response->message : $error_message ),
							'data'     => isset( $payment_response->data ) ? $payment_response->data : null,
						)
					);
				} catch ( \Error $e ) {

					wp_send_json_error(
						array(
							'error'    => true,
							'messages' => array( 'Fatal Request Error' ),
							'data'     => null,
						)
					);
				}
			}
			// {"temporary_order_id":"84f7809bde45a1b6de15ae93a6e4bf4a","uuid":"2219ab84-f8b4-45d6-abb9-1c9da38a461e"}
			update_option(
				'rkfl_partial_payment_cache_' . $email,
				array(
					'temporary_order_id' => $temporary_order_id,
					'uuid'               => $payment_response->result->uuid,
				),
				false
			);
			// update_option( 'rkfl_partial_payment_cache_'.$email,
			// array(
			// 'temporary_order_id' => '84f7809bde45a1b6de15ae93a6e4bf4a',
			// 'uuid'=>'2219ab84-f8b4-45d6-abb9-1c9da38a461e'
			// ),
			// false );

			wp_send_json_success(
				array(
					'encrypted_req'      => $encrypted_req,
					'temporary_order_id' => $temporary_order_id,
					'uuid'               => $payment_response,
				)
			);
		} catch ( \Throwable $th ) {

			return wp_send_json_error(
				array(
					'error'    => true,
					'messages' => array( 'Fatal Request Error' ),
					'data'     => null,
				)
			);
		}
	}
	public static function rocketfuel_process_checkout() {

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '_wc_rkfl_start_checkout_nonce' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-gateway-paypal-express-checkout' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// Intercept process_checkout call to exit after validation.
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'maybe_start_checkout' ), 10, 2 );

		WC()->checkout->process_checkout();
	}
	private static function checkProductForSub( $cart ) {
		$result = false;

		foreach ( $cart as $cart_item ) {

			$_product = wc_get_product( $cart_item['product_id'] );

			if ( class_exists( 'WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $_product ) ) {
				$result = true;
			}
		}
		return $result;
	}
	/**
	 * Report validation errors if any, or else save form data in session and proceed with checkout flow.
	 *
	 * @since 1.6.4
	 */
	public static function maybe_start_checkout( $data, $errors = null ) {
		if ( is_null( $errors ) ) {
			// Compatibility with WC <3.0: get notices and clear them so they don't re-appear.
			$error_messages = wc_get_notices( 'error' );
			wc_clear_notices();
		} else {
			$error_messages = $errors->get_error_messages();
		}
		try {

			$billing_email = isset( $_POST['billing_email'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_email'] ) ) : '';

			if (
				! empty( $billing_email ) &&
				email_exists( $billing_email ) &&
				( ! is_user_logged_in() ||
					( is_user_logged_in() &&
						wp_get_current_user()->user_email !== $billing_email )
				) && self::checkProductForSub( WC()->cart->get_cart() )
			) {
				$error_messages[] = 'An account is already registered with your email. Kindly login';
			}
		} catch ( \Throwable $th ) {
			$error_messages = $th->getMessage();
		}

		if ( empty( $error_messages ) ) {
			self::set_customer_data( wc_clean( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			self::start_checkout( false );
		} else {
			wp_send_json_error( array( 'messages' => $error_messages ) );
		}
		exit;
	}
	/**
	 * Set Express Checkout and return token in response.
	 *
	 * @param bool $skip_checkout  Whether checkout screen is being bypassed.
	 *
	 * @since 1.6.4
	 */
	protected static function start_checkout() {
		try {
			self::process_user_data();
		} catch ( \Error $e ) {
			wp_send_json_error( array( 'messages' => array( $e->getMessage() ) ) );
		}
	}
	/**
	 * Store checkout form data in customer session.
	 *
	 * @since 1.6.4
	 */
	protected static function set_customer_data( $data ) {
		$customer = WC()->customer;

        // phpcs:disable WordPress.WhiteSpace.OperatorSpacing.SpacingBefore
		$billing_first_name = empty( $data['billing_first_name'] ) ? '' : wc_clean( $data['billing_first_name'] );
		$billing_last_name  = empty( $data['billing_last_name'] )  ? '' : wc_clean( $data['billing_last_name'] );
		$billing_country    = empty( $data['billing_country'] )    ? '' : wc_clean( $data['billing_country'] );
		$billing_address_1  = empty( $data['billing_address_1'] )  ? '' : wc_clean( $data['billing_address_1'] );
		$billing_address_2  = empty( $data['billing_address_2'] )  ? '' : wc_clean( $data['billing_address_2'] );
		$billing_city       = empty( $data['billing_city'] )       ? '' : wc_clean( $data['billing_city'] );
		$billing_state      = empty( $data['billing_state'] )      ? '' : wc_clean( $data['billing_state'] );
		$billing_postcode   = empty( $data['billing_postcode'] )   ? '' : wc_clean( $data['billing_postcode'] );
		$billing_phone      = empty( $data['billing_phone'] )      ? '' : wc_clean( $data['billing_phone'] );
		$billing_email      = empty( $data['billing_email'] )      ? '' : wc_clean( $data['billing_email'] );
        // phpcs:enable

		if ( isset( $data['ship_to_different_address'] ) ) {
            // phpcs:disable WordPress.WhiteSpace.OperatorSpacing.SpacingBefore
			$shipping_first_name = empty( $data['shipping_first_name'] ) ? '' : wc_clean( $data['shipping_first_name'] );
			$shipping_last_name  = empty( $data['shipping_last_name'] )  ? '' : wc_clean( $data['shipping_last_name'] );
			$shipping_country    = empty( $data['shipping_country'] )    ? '' : wc_clean( $data['shipping_country'] );
			$shipping_address_1  = empty( $data['shipping_address_1'] )  ? '' : wc_clean( $data['shipping_address_1'] );
			$shipping_address_2  = empty( $data['shipping_address_2'] )  ? '' : wc_clean( $data['shipping_address_2'] );
			$shipping_city       = empty( $data['shipping_city'] )       ? '' : wc_clean( $data['shipping_city'] );
			$shipping_state      = empty( $data['shipping_state'] )      ? '' : wc_clean( $data['shipping_state'] );
			$shipping_postcode   = empty( $data['shipping_postcode'] )   ? '' : wc_clean( $data['shipping_postcode'] );
            // phpcs:enable
		} else {
			$shipping_first_name = $billing_first_name;
			$shipping_last_name  = $billing_last_name;
			$shipping_country    = $billing_country;
			$shipping_address_1  = $billing_address_1;
			$shipping_address_2  = $billing_address_2;
			$shipping_city       = $billing_city;
			$shipping_state      = $billing_state;
			$shipping_postcode   = $billing_postcode;
		}

		$customer->set_shipping_country( $shipping_country );
		$customer->set_shipping_address( $shipping_address_1 );
		$customer->set_shipping_address_2( $shipping_address_2 );
		$customer->set_shipping_city( $shipping_city );
		$customer->set_shipping_state( $shipping_state );
		$customer->set_shipping_postcode( $shipping_postcode );

		if ( version_compare( \WC_VERSION, '3.0', '<' ) ) {
			$customer->shipping_first_name = $shipping_first_name;
			$customer->shipping_last_name  = $shipping_last_name;
			$customer->billing_first_name  = $billing_first_name;
			$customer->billing_last_name   = $billing_last_name;

			$customer->set_country( $billing_country );
			$customer->set_address( $billing_address_1 );
			$customer->set_address_2( $billing_address_2 );
			$customer->set_city( $billing_city );
			$customer->set_state( $billing_state );
			$customer->set_postcode( $billing_postcode );
			$customer->billing_phone = $billing_phone;
			$customer->billing_email = $billing_email;
		} else {
			$customer->set_shipping_first_name( $shipping_first_name );
			$customer->set_shipping_last_name( $shipping_last_name );
			$customer->set_billing_first_name( $billing_first_name );
			$customer->set_billing_last_name( $billing_last_name );

			$customer->set_billing_country( $billing_country );
			$customer->set_billing_address_1( $billing_address_1 );
			$customer->set_billing_address_2( $billing_address_2 );
			$customer->set_billing_city( $billing_city );
			$customer->set_billing_state( $billing_state );
			$customer->set_billing_postcode( $billing_postcode );
			$customer->set_billing_phone( $billing_phone );
			$customer->set_billing_email( $billing_email );
		}
	}
}
