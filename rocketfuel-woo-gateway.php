<?php
/**
 * Plugin Name: RocketFuel Payment Gateway
 * Domain Path: /Languages/
 * Plugin URI: https://rocketfuelblockchain.com
 * Description: Pay with crypto using Rocketfuel
 * Author: Rocketfuel Team
 * Author URI: https://rocketfuelblockchain.com
 * Version: 2.0.0
 * WC requires at least: 3.0.0
 * WC tested up to: 5.1
 * Text Domain: rocketfuel
 */

use Rocketfuel_Gateway\Plugin;

if (!defined('ABSPATH'))
    die('A cup does not drink what it holds?');

if (rocketfuel_check_woocommerce_is_active()) {
    require_once(plugin_dir_path(__FILE__) . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
    Plugin::init(__FILE__);
} else {
    add_action('admin_notices','rocketfuel_check_woocommerce_is_not_active_notice');
}
/**
 * Display a notice if WooCommerce is not installed
 */
function rocketfuel_check_woocommerce_is_not_active_notice()
{
    echo '<div class="error"><p><strong>' . sprintf(__('Rocketfuel requires WooCommerce to be installed and active. Click %s to install WooCommerce.', 'rocketfuel'), '<a href="' . admin_url('plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true&width=772&height=539') . '" class="thickbox open-plugin-details-modal">here</a>') . '</strong></p></div>';
}
function rocketfuel_check_woocommerce_is_active()
{

    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        return true;
    }
    return false;
}
