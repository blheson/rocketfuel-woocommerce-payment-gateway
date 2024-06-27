<?php
/**
 * Plugin Name: RocketFuel Payment Gateway
 * Domain Path: /Languages/
 * Plugin URI: https://rocketfuelblockchain.com
 * Description: Pay with crypto using Rocketfuel
 * Author: Rocketfuel Team
 * Author URI: https://rocketfuelblockchain.com/integrations
 * Version: 3.2.3.2
 * WC requires at least: 3.0.0
 * WC tested up to: 6.4.3
 * Text Domain: rocketfuel-payment-gateway
 * Licence: GPLv3
 *
 * @package Rocketfuel
 */

use Rocketfuel_Gateway\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'A cup does not drink what it holds?' );
}
define( 'WC_ROCKETFUEL_MAIN_FILE', __FILE__ );


if ( rocketfuel_check_woocommerce_is_active() ) {
	define( 'ROCKETFUEL_VER', '3.2.3.2' );

	require_once plugin_dir_path( __FILE__ ) . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
	Plugin::init( __FILE__ );
} else {
	add_action( 'admin_notices', 'rocketfuel_check_woocommerce_is_not_active_notice' );
}

/**
 * Display a notice if WooCommerce is not installed
 */
function rocketfuel_check_woocommerce_is_not_active_notice() {
	echo '<div class="error"><p><strong>' . sprintf( __( 'Rocketfuel requires WooCommerce to be installed and active. Click %s to install WooCommerce.', 'rocketfuel-payment-gateway' ), '<a href="' . esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true&width=772&height=539' ) ) . '" class="thickbox open-plugin-details-modal">here</a>' ) . '</strong></p></div>';
}
/**
 * Check if Woocommerce is active
 */
function rocketfuel_check_woocommerce_is_active() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
		return true;
	}
	return false;
}

/**
 * Registers WooCommerce Blocks integration.
 */
function rocketfuel_gateway_woocommerce_block_support() {
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {

		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new Rocketfuel_Gateway\Controllers\WC_Gateway_Rocketfuel_Blocks_Support() );
			}
		);
	}
}
add_action( 'woocommerce_blocks_loaded', 'rocketfuel_gateway_woocommerce_block_support' );
