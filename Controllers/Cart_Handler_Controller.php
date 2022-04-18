<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

class Cart_Handler_Controller
{

    public static function register()
    {

        // add_action('wc_ajax_wc_ppec_update_shipping_costs', array($this, 'wc_ajax_update_shipping_costs'));
        add_action('wc_ajax_wc_rkfl_start_checkout', array($this, 'rocketfuel_process_checkout'));
    }
    public static function process_user_data()
    {


        $temporary_order_id = md5(microtime());
        $gateway = new Rocketfuel_Gateway_Controller();
        $cart = $gateway->sort_cart(WC()->cart->get_cart(), $temporary_order_id);

        $merchant_cred = array(
            'email' => $gateway->email,
            'password' => $gateway->password
        );



        $phone = method_exists(WC()->customer, 'get_shipping_phone') ?
            WC()->customer->get_shipping_phone() : false;
        $zipcode = method_exists(WC()->customer, 'get_shipping_postcode') ?
            WC()->customer->get_shipping_postcode() : false;
        $email = method_exists(WC()->customer, 'get_email') ?
            WC()->customer->get_email() : false;

        $country_code = method_exists(WC()->customer, 'get_shipping_country') ?
            WC()->customer->get_shipping_country() : '';

        $country = WC()->countries->countries[$country_code];

        $state_code = method_exists(WC()->customer, 'get_shipping_state') ?
            WC()->customer->get_shipping_state() : '';


        $states = $state_code ? WC()->countries->get_states($country_code) :  [];


        $state  = !empty($states[$state_code]) ? $states[$state_code] : $state_code;


        $data = array(
            'cred' => $merchant_cred,
            'endpoint' => $gateway->endpoint,
            'body' => array(
                'amount' => WC()->cart->total,
                'cart' => $cart,
                'merchant_id' => $gateway->merchant_id,
                'shippingAddress' => array(
                    "phoneNo" =>  $phone ? $phone : (method_exists(WC()->customer, 'get_billing_phone') ?
                        WC()->customer->get_billing_phone() : ''),
                    "email" => $email ? $email : (method_exists(WC()->customer, 'get_billing_email') ?
                        WC()->customer->get_billing_email() : ''),
                    "address1" => method_exists(WC()->customer, 'get_shipping_address') ?
                        WC()->customer->get_shipping_address() : '',
                    "address2" =>  method_exists(WC()->customer, 'get_shipping_address_2') ?
                        WC()->customer->get_shipping_address_2() : '',
                    "state" =>   $state,
                    "city" =>  method_exists(WC()->customer, 'get_shipping_city') ?
                        WC()->customer->get_shipping_city() : '',
                    "zipcode" => $zipcode,
                    "country" => $country,
                    "landmark" => "",
                    "firstname" => isset($_GET['shipping_firstname']) ?
                        sanitize_text_field($_GET['shipping_firstname']) : (method_exists(WC()->customer, 'get_shipping_first_name') ?
                            WC()->customer->get_shipping_first_name() :
                            ''
                        ),
                    "lastname" => isset($_GET['shipping_lastname']) ?
                        sanitize_text_field($_GET['shipping_lastname']) : (method_exists(WC()->customer, 'get_shipping_last_name') ?
                            WC()->customer->get_shipping_last_name() : ''),
                ),

                'currency' => get_woocommerce_currency("USD"),

                'order' => (string)$temporary_order_id,
                'redirectUrl' => ''
            )
        );

        unset($gateway);

        $payment_response = Process_Payment_Controller::process_payment($data);

        if (!$payment_response) {
            wp_send_json_error(array('error' => true, 'message' => 'Payment cannot be completed'));
        }

        $result = json_decode($payment_response);

        wp_send_json_success(array('temporary_order_id'=>$temporary_order_id,'uuid'=>$result));
    }
    public static function rocketfuel_process_checkout()
    {

        if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], '_wc_rkfl_start_checkout_nonce')) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            wp_die(__('Cheatin&#8217; huh?', 'woocommerce-gateway-paypal-express-checkout')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        // Intercept process_checkout call to exit after validation.
        add_action('woocommerce_after_checkout_validation', array(__CLASS__, 'maybe_start_checkout'), 10, 2);

        WC()->checkout->process_checkout();
    }
    /**
     * Report validation errors if any, or else save form data in session and proceed with checkout flow.
     *
     * @since 1.6.4
     */
    public static function maybe_start_checkout($data, $errors = null)
    {
        if (is_null($errors)) {
            // Compatibility with WC <3.0: get notices and clear them so they don't re-appear.
            $error_messages = wc_get_notices('error');
            wc_clear_notices();
        } else {
            $error_messages = $errors->get_error_messages();
        }

        if (empty($error_messages)) {
            self::set_customer_data($_POST); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            self::start_checkout(false);
        } else {
            wp_send_json_error(array('messages' => $error_messages));
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
    protected static function start_checkout()
    {
        try {
            self::process_user_data();
            // wp_send_json_success(array('token' => WC()->session->paypal->token));
        } catch (Error $e) {
            wp_send_json_error(array('messages' => array($e->getMessage())));
        }
    }
    /**
     * Store checkout form data in customer session.
     *
     * @since 1.6.4
     */
    protected static function set_customer_data($data)
    {
        $customer = WC()->customer;

        // phpcs:disable WordPress.WhiteSpace.OperatorSpacing.SpacingBefore
        $billing_first_name = empty($data['billing_first_name']) ? '' : wc_clean($data['billing_first_name']);
        $billing_last_name  = empty($data['billing_last_name'])  ? '' : wc_clean($data['billing_last_name']);
        $billing_country    = empty($data['billing_country'])    ? '' : wc_clean($data['billing_country']);
        $billing_address_1  = empty($data['billing_address_1'])  ? '' : wc_clean($data['billing_address_1']);
        $billing_address_2  = empty($data['billing_address_2'])  ? '' : wc_clean($data['billing_address_2']);
        $billing_city       = empty($data['billing_city'])       ? '' : wc_clean($data['billing_city']);
        $billing_state      = empty($data['billing_state'])      ? '' : wc_clean($data['billing_state']);
        $billing_postcode   = empty($data['billing_postcode'])   ? '' : wc_clean($data['billing_postcode']);
        $billing_phone      = empty($data['billing_phone'])      ? '' : wc_clean($data['billing_phone']);
        $billing_email      = empty($data['billing_email'])      ? '' : wc_clean($data['billing_email']);
        // phpcs:enable

        if (isset($data['ship_to_different_address'])) {
            // phpcs:disable WordPress.WhiteSpace.OperatorSpacing.SpacingBefore
            $shipping_first_name = empty($data['shipping_first_name']) ? '' : wc_clean($data['shipping_first_name']);
            $shipping_last_name  = empty($data['shipping_last_name'])  ? '' : wc_clean($data['shipping_last_name']);
            $shipping_country    = empty($data['shipping_country'])    ? '' : wc_clean($data['shipping_country']);
            $shipping_address_1  = empty($data['shipping_address_1'])  ? '' : wc_clean($data['shipping_address_1']);
            $shipping_address_2  = empty($data['shipping_address_2'])  ? '' : wc_clean($data['shipping_address_2']);
            $shipping_city       = empty($data['shipping_city'])       ? '' : wc_clean($data['shipping_city']);
            $shipping_state      = empty($data['shipping_state'])      ? '' : wc_clean($data['shipping_state']);
            $shipping_postcode   = empty($data['shipping_postcode'])   ? '' : wc_clean($data['shipping_postcode']);
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

        $customer->set_shipping_country($shipping_country);
        $customer->set_shipping_address($shipping_address_1);
        $customer->set_shipping_address_2($shipping_address_2);
        $customer->set_shipping_city($shipping_city);
        $customer->set_shipping_state($shipping_state);
        $customer->set_shipping_postcode($shipping_postcode);

        if (version_compare(WC_VERSION, '3.0', '<')) {
            $customer->shipping_first_name = $shipping_first_name;
            $customer->shipping_last_name  = $shipping_last_name;
            $customer->billing_first_name  = $billing_first_name;
            $customer->billing_last_name   = $billing_last_name;

            $customer->set_country($billing_country);
            $customer->set_address($billing_address_1);
            $customer->set_address_2($billing_address_2);
            $customer->set_city($billing_city);
            $customer->set_state($billing_state);
            $customer->set_postcode($billing_postcode);
            $customer->billing_phone = $billing_phone;
            $customer->billing_email = $billing_email;
        } else {
            $customer->set_shipping_first_name($shipping_first_name);
            $customer->set_shipping_last_name($shipping_last_name);
            $customer->set_billing_first_name($billing_first_name);
            $customer->set_billing_last_name($billing_last_name);

            $customer->set_billing_country($billing_country);
            $customer->set_billing_address_1($billing_address_1);
            $customer->set_billing_address_2($billing_address_2);
            $customer->set_billing_city($billing_city);
            $customer->set_billing_state($billing_state);
            $customer->set_billing_postcode($billing_postcode);
            $customer->set_billing_phone($billing_phone);
            $customer->set_billing_email($billing_email);
        }
    }
}
