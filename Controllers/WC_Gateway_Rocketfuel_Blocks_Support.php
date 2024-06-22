<?php
namespace Rocketfuel_Gateway\Controllers;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Rocketfuel_Gateway\Plugin;

final class WC_Gateway_Rocketfuel_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'rocketfuel_gateway';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_rocketfuel_gateway_settings', array() );

		// add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'failed_payment_notice' ), 8, 2 );
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();
		return $payment_gateways['rocketfuel_gateway']->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_asset_path = plugins_url( '/assets/js/blocks/frontend/blocks.asset.php', WC_ROCKETFUEL_MAIN_FILE );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => Plugin::get_ver(),
			);

		$script_url = plugins_url( '/assets/js/blocks/frontend/blocks.min.js', WC_ROCKETFUEL_MAIN_FILE );

		wp_register_script(
			'wc-rocketfuel-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-rocketfuel-blocks', 'woo-rocketfuel', );
		}

		return array( 'wc-rocketfuel-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();
		$gateway                = $payment_gateways['rocketfuel_gateway'];

		return array(
			'title'       => $this->get_setting( 'title' ),
			'button_text' => $this->get_setting( 'button_text' ),
			'description' => $this->get_setting( 'description' ),
			'environment' => $this->get_setting( 'environment' ),
			'supports'    => array_filter( $gateway->supports, array( $gateway, 'supports' ) ),
			// 'logo_urls'         => array( $payment_gateways['rocketfuel_gateway']->get_logo_url() ),
		);
	}

	/**
	 * Add failed payment notice to the payment details.
	 *
	 * @param PaymentContext $context Holds context for the payment.
	 * @param PaymentResult  $result  Result object for the payment.
	 */
	// public function failed_payment_notice( PaymentContext $context, PaymentResult &$result ) {
	// if ( 'rocketfuel_gateway' === $context->payment_method ) {
	// add_action(
	// 'wc_gateway_rocketfuel_gateway_process_payment_error',
	// function( $failed_notice ) use ( &$result ) {
	// $payment_details                 = $result->payment_details;
	// $payment_details['errorMessage'] = wp_strip_all_tags( $failed_notice );
	// $result->set_payment_details( $payment_details );
	// }
	// );
	// }
	// }
}
