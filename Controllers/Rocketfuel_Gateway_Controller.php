<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

class Rocketfuel_Gateway_Controller extends \WC_Payment_Gateway
{

    public function __construct()
    {
        $this->id = 'rocketfuel_gateway';
        // $this->icon = '/d/d.png';
        $this->has_fields = false;
        $this->method_title = 'Rocketfuel';
        $this->method_description = 'Pay with Crypto using Rocketfuel';
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title');

        $this->endpoint = 'https://app.rocketfuelblockchain.com/api';
        $this->public_key = $this->get_option('public_key');
$this->description = $this->get_option('description');
        $this->password = $this->get_option('password');
        $this->email = $this->get_option('email');

        $this->supports = array('products');

        $this->merchant_id = $this->get_option('merchant_id');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }
    public function init_form_fields()
    {
        $this->form_fields = apply_filters('rocketfuel_admin_fields', array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'rocketfuel'),
                'type' => 'checkbox',
                'label' => __('Enable Rocketfuel', 'rocketfuel'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'rocketfuel'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'rocketfuel'),
                'default' => __('Rocketfuel', 'rocketfuel'),
                'desc_tip'      => true,
            ),
            'description' => array(
                'title' => __('Customer Message', 'woocommerce'),
                'type' => 'textarea',
                'default' => 'Pay for your order with RocketFuel'
            ),

            'merchant_id' => array(
                'title' => __('Merchant ID', 'rocketfuel'),
                'type' => 'text',
                'default' => ''
            ),
            'public_key' => array(
                'title' => __('Public Key', 'rocketfuel'),
                'type' => 'textarea',
                'default' => ''
            ), 'email' => array(
                'title' => __('Email', 'rocketfuel'),
                'type' => 'text',
                'default' => ''
            ), 'password' => array(
                'title' => __('Password', 'rocketfuel'),
                'type' => 'password',
                'default' => ''
            ),
            // 'endpoint' => array(
            //     'title' => __('Rocketfuel url', 'rocketfuel'),
            //     'type' => 'select',
            //     'default' => '',
            //     'options' => array(
            //         'https://app.rocketfuelblockchain.com/api' => 'https://iframe.rocketfuelblockchain.com',
            //     )
            // ),
            'callback_url' => array(
                'title' => __('Callback URL', 'rocketfuel'),
                'type' => 'checkbox',
                'label' => __(rest_url() . Plugin::get_api_route_namespace() . '/payment', 'rocketfuel'),
                'description' => __('Callback URL for Rocketfuel', 'rocketfuel'),
                'default' => '',
                'css' => 'display:none'
                // 'desc_tip'      => true,
            )

        ));
    }
    /**
     * Parse cart items and prepare for order
     * @param array $items 
     * @return array
     */
    public function sortCart($items)
    {
        $data = array();
        foreach ($items as $cart_item) {
            $data[] = array(
                'name' => $cart_item['data']->get_title(),
                'id' => (string)$cart_item['product_id'],
                'price' => $cart_item['data']->get_price(),
                'quantity' => (string)$cart_item['quantity']
            );
        }

        return $data;
    }
    /**
     * Update order when payment has been confirmed
     * @param WP_REST_REQUEST $request_data
     * @return void
     */
    public function update_order($request_data)
    {
        $data = $request_data->get_params();
        if (!$data['order_id']) {
            echo json_encode(array('status' => 'failed', 'message' => 'Order was not updated'));
            exit;
        }

        $order_id = $data['order_id'];
        $status = $data['status'];
        if (!$order_id) {
            echo json_encode(array('status' => 'failed', 'message' => 'Order was not updated'));
            exit;
        }

        $order = wc_get_order($order_id);
        $data = $order->update_status($status);
        echo json_encode(array('status' => 'ok', 'message' => 'Order was updated'));
        exit;
    }

    /**
     * Process payment and redirect user to payment page
     * @param int $order_id
     * @return false|array
     */
    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = wc_get_order($order_id);
        $cart = $this->sortCart(WC()->cart->get_cart());
        $data = array(
            'cred' => array(
                'email' => $this->email,
                'password' => $this->password
            ),
            'endpoint' => $this->endpoint,
            'body' => array(
                'amount' => $order->get_total(),
                'cart' => $cart,
                'merchant_id' => $this->merchant_id,
                'currency' => "USD",
                'order' => (string)$order_id,
                'redirectUrl' => ''
            )
        );

        $payment_response = Process_Payment_Controller::processPayment($data);

        if (!$payment_response) {
            return;
        }
        $result = json_decode($payment_response);

        if (!isset($result->result) && !isset($result->result->url)) {
            wc_add_notice(__('Failed to place order', 'rocketfuel'), 'error');
            return false;
        }

        $urlArr = explode('/', $result->result->url);
        $uuid = $urlArr[count($urlArr) - 1];
    

        // Remove cart
        $woocommerce->cart->empty_cart();

        // // Return thankyou redirect
        // $pay_link = get_permalink(get_option(Plugin::$prefix . 'process_payment_page_id'));

        // $order_key = explode('order-received', $this->get_return_url($order))[1];
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order). "&uuid=" . $uuid 
        );
        
    }
}
