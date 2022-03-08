<?php

/**
 * Thank you page class for Rocketfuel
 * 
 * @author UdorBlessing
 */

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;
use Rocketfuel_Gateway\Services\Subscription_Service;

class Woocommerce_Controller
{
    public static function register()
    {

        add_action('plugins_loaded', array(__CLASS__, 'init_rocketfuel_gateway_class'));

        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway_class'));

        add_action('init', array(__CLASS__, 'register_partial_payment_order_status'));

        add_filter('wc_order_statuses', array(__CLASS__, 'add_partial_payment_to_order_status'));

        add_action('wp_ajax_nopriv_rocketfuel_process_user_data', array(__CLASS__, 'process_user_data'));

        add_action('wp_ajax_rocketfuel_process_user_data', array(__CLASS__, 'process_user_data'));

        if (!is_admin()) {

            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_action'));
        }

        add_action('woocommerce_checkout_update_order_meta', array(__CLASS__, 'add_temp_id_to_order'));

        add_action('woocommerce_checkout_create_order', array(__CLASS__, 'checkout_create_order'));

        add_action('woocommerce_subscription_status_cancelled', array(__CLASS__, 'cancel_subscription_order'));

        add_action('woocommerce_subscription_status_pending-cancelled', array(__CLASS__, 'cancel_subscription_order'));
    }
    /**
     * Cancel Subscription
     * @param 
     */
    public function cancel_subscription_order($subscription)
    {

        // wcs_get_subscriptions_for_order( $order );

        $order_id = $subscription->get_parent_id();

        file_put_contents(__DIR__ . '/log.json', "\n" . 'Order Id returned from cancel_subscription_order: -> ' . json_encode($order_id) . "\n", FILE_APPEND);

        if (!$order_id) {
            return false;
        }

        $temporary_order_id = get_post_meta($order_id, 'rocketfuel_temp_orderid', true);

        file_put_contents(__DIR__ . '/log.json', "\n" . 'temporary_order_id returned from cancel_subscription_order: -> ' . json_encode($temporary_order_id) . "\n", FILE_APPEND);

        if (!$temporary_order_id) {
            return false;
        }

        $gateway = new Rocketfuel_Gateway_Controller();

        $order_items = $subscription->get_items();

        // Loop through order items
        foreach ($order_items as $item_id => $item) {

            $product = $item->get_product();

            // Or to get the simple subscription or the variation subscription product ID
            $_product_id = $product->get_id();

            $payload = array(
                'merchant_id' => $gateway->merchant_id,
                'merchant_auth' => $gateway->merchant_auth(),
                'subscription_id' => $temporary_order_id . '_' . $_product_id,
                'endpoint'=> $gateway->endpoint
            );

            file_put_contents(__DIR__ . '/log.json', "\n" . 'payload for cancel_subscription_order: -> ' . json_encode($payload) . "\n", FILE_APPEND);
            

            $response = Subscription_Service::cancel_subscription($payload);
            
            $response_body = wp_remote_retrieve_body($response);

            file_put_contents(__DIR__ . '/log.json', "\n" . 'Respponse bodty for cancel_subscription_order: -> ' . json_encode($response_body) . "\n", FILE_APPEND);

            file_put_contents(__DIR__ . '/log.json', "\n" . 'Respponse for cancel_subscription_order: -> ' . json_encode($response) . "\n", FILE_APPEND);

        }
    }
    /**
     * Check order details before submit
     * 
     */
    public function checkout_create_order()
    {


        if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'rocketfuel_gateway' && $_POST['payment_status_rocketfuel'] !== 'complete') {

            $error_text = __("Rocketfuel Error: Payment not confirmed", "woocommerce");

            throw new \Exception($error_text);

            return false;
        }
    }

    /**
     * Keep Temporary order_id for webhook
     * @param string $order_id
     */
    public function add_temp_id_to_order($order_id)
    {

        if (isset($_POST)) {

            update_post_meta($order_id, 'rocketfuel_temp_orderid', sanitize_text_field($_POST['temp_orderid_rocketfuel']));

            if (null !== $_POST['order_status_rocketfuel'] && 'wc-on-hold' !==  $_POST['order_status_rocketfuel']) {
                try {
                    $order = wc_get_order($order_id);

                    $order->update_status($_POST['order_status_rocketfuel']);
                } catch (\Throwable $th) {
                    //silently ignore
                }
            }
        }
    }

    public static function process_user_data()
    {


        $gateway = new Rocketfuel_Gateway_Controller();

        $cart = $gateway->sortCart(WC()->cart->get_cart());

        $merchant_cred = array(
            'email' => $gateway->email,
            'password' => $gateway->password
        );

        $data = array(
            'cred' => $merchant_cred,
            'endpoint' => $gateway->endpoint,
            'body' => array(
                'amount' => WC()->cart->total,
                'cart' => $cart,
                'merchant_id' => $gateway->merchant_id,
                'currency' => get_woocommerce_currency("USD"),
                'order' => (string) microtime(),
                'redirectUrl' => ''
            )
        );
        unset($gateway);

        $payment_response = Process_Payment_Controller::process_payment($data);

        if (!$payment_response) {
            wp_send_json_error(array('error' => true, 'message' => 'Payment cannot be completed'));
        }

        $result = json_decode($payment_response);

        wp_send_json_success($result);
    }
    /**
     * Enqueue Rocketfuel scripts
     */
    public static function enqueue_action()
    {

        if (!(is_checkout() || is_wc_endpoint_url('order-received') || is_wc_endpoint_url())) {
            return false;
        }

        wp_enqueue_script('rkfl-script', Plugin::get_url('assets/js/rkfl.js'), array(), time());
    }

    public static function add_gateway_class($methods)
    {
        if (class_exists('WC_Subscriptions_Order') && class_exists('WC_Payment_Gateway')) {
            $methods[] = 'Rocketfuel_Gateway\Controllers\Rocketfuel_Gateway_Subscription_Controller';
        } else {
            $methods[] = 'Rocketfuel_Gateway\Controllers\Rocketfuel_Gateway_Controller';
        }

        return $methods;
    }
    /**
     * Initiate the gateway
     */
    public static function init_rocketfuel_gateway_class()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        require_once 'Rocketfuel_Gateway_Controller.php';
    }
    /**
     * Register custom order status
     */
    public static function register_partial_payment_order_status()
    {
        $args = array(
            'label'                     => 'Partial payment',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop('Partial Payment <span class="count">(%s)</span>', 'Partial Payments <span class="count">(%s)</span>')
        );
        register_post_status('wc-partial-payment', $args);
    }
    /**
     * Add custom order status
     * @param string $order_status
     * @return array 
     */
    public static function add_partial_payment_to_order_status($order_statuses)
    {
        $new_order_statuses = array();
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;
            if ('wc-on-hold' === $key) {
                $new_order_statuses['wc-partial-payment'] = 'Partial payment';
            }
        }
        return $new_order_statuses;
    }
}
