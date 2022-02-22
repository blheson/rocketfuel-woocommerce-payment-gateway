<?php

/**
 * Thank you page class for Rocketfuel
 * 
 * @author UdorBlessing
 */

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

class Woocommerce_Controller
{
    public static function register()
    {
        add_action('plugins_loaded', array(__CLASS__, 'init_rocketfuel_gateway_class'));
        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway_class'));
        add_action('init', array(__CLASS__, 'register_partial_payment_order_status'));
        // add_action('woocommerce_thankyou', array(__CLASS__, 'administer_thank_you_page'));
        add_filter('wc_order_statuses', array(__CLASS__, 'add_partial_payment_to_order_status'));
        add_action('wp_ajax_nopriv_rocketfuel_process_user_data', array(__CLASS__, 'process_user_data'));
        add_action('wp_ajax_rocketfuel_process_user_data', array(__CLASS__, 'process_user_data'));
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_action'));
        }
        add_action('woocommerce_after_order_notes', array(__CLASS__, 'rkfl_custom_checkout_field'));

        add_action('woocommerce_checkout_update_order_meta', array(__CLASS__, 'add_temp_id_to_order'));
    }
    public function add_temp_id_to_order($order_id)
    {

        if (isset($_POST)) {
            file_put_contents(__DIR__ . '/afterpost.json', "\n" .     json_encode($_POST) . "\n", FILE_APPEND);
            update_post_meta($order_id, 'rocketfuel_temp_orderid', sanitize_text_field($_POST['uuid_rocketfuel']));
            if (null !== $_POST['status'] && 'wc-on-hold' !==  $_POST['status']) {
                try {
                    $order = wc_get_order($order_id);

                    $order->update_status($_POST['status']);
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
        }

        // file_put_contents(__DIR__ . '/afterdat.json', "\n" . 	json_encode($data) . "\n", FILE_APPEND);



        // if (!empty($_POST['rkfl-temp-order'])) {



        //     }
    }

    public static function process_user_data()
    {


        $gateway = new Rocketfuel_Gateway_Controller();
        $response = new \stdClass();


        $cart = $gateway->sortCart(WC()->cart->get_cart());
        file_put_contents(__DIR__ . '/count.json', '2');

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
    public static function enqueue_action()
    {
        wp_enqueue_script('rkfl-script', Plugin::get_url('assets/js/rkfl.js'), array(), time());
    }
    /**
     * 
     */
    public static function administer_thank_you_page($order_id)
    {
?>
        <style>
            .rocketfuel_process_payment {
                text-align: center;
                display: flex;
                justify-content: center;
                align-content: center;
                align-items: baseline;
            }

            #rocketfuel_process_payment_button {
                background-color: #229633;
                border: #229633;
            }

            h3.indicate_text {
                margin: 0;
                font-size: 32px;
                margin-right: 10px;
                color: #fff;
            }

            .loader_rocket {
                border: 1px solid #000000;
                border-top: 1px solid #ffffff;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                animation: spin 0.4s linear infinite;
            }

            .rocket_fuel_payment_overlay_wrapper {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                align-content: center;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

            #rocket_fuel_payment_overlay_gateway {
                width: 100%;
                top: 0;
                right: 0;
                height: 100%;
                z-index: 100000 !important;
                position: fixed;
                background: rgb(0 0 0 / 97%);
                display: flex;
            }

            #iframeWrapper {
                z-index: 100001 !important;
            }

            .rocket_fuel_payment_overlay_wrapper_gateway {
                width: 100%;
                display: flex;
                align-items: center;
                align-content: center;
                justify-content: center;
            }

            #rocketfuel_retrigger_payment button {
                text-align: center;
                background: #f0833c !important;
                padding: 0px;
                border: none;
                width: 300px;
                padding-bottom: 2px;
                height: 48px;
                font-size: 17px;
                margin-top: 12px;
                border-radius: 3px;
                font-weight: 300;
                color: #fff;
                cursor: pointer;
            }


            #rocketfuel_retrigger_payment {
                display: none;
            }

            #rocketfuel_retrigger_payment button:hover {
                outline: none;
                border: none;

                background-color: #e26f02 !important;
                border-color: #e26f02 !important;

            }

            .rocketfuel_exit_plan_wrapper {
                display: flex;
                text-align: center;
                justify-content: center;
                align-items: center;
                margin-top: 30px;
            }

            .rocketfuel_exit_plan_wrapper figure {
                width: 14px;
                height: 37px;
                margin: 0;
                right: 0px;
                position: relative;
                transition: right 700ms;
                display: inline-block;

            }

            .proceed-forward-rkfl:hover figure {
                right: -6px;
                transition: right 200ms;
            }

            /* .rocketfuel_exit_plan_wrapper:hover a {
                color: #ddd;
            } */

            .rocketfuel_exit_plan_wrapper a.completed-button-rkfl {
                border: 1px solid #ffffff4d;
                border-radius: 4px;
                padding: 2px 10px;

            }

            .rocketfuel_exit_plan_wrapper a.proceed-forward-rkfl {
                padding-right: 10px;
            }

            .rocketfuel_exit_plan_wrapper a {

                text-decoration: none;
                color: #fff !important;
                font-size: 12px;

            }

            .rocketfuel_exit_plan_wrapper a:focus {
                outline: none !important;
                text-decoration: none !important;
                background: transparent !important;
            }
        </style>

        <input type="hidden" name="rocket_order_id" value="<?php echo esc_attr($order_id) ?>">

        <input type="hidden" name="rest_url" value="<?php echo esc_attr(rest_url() . Plugin::get_api_route_namespace() . '/update_order') ?>">

        <div id="rocket_fuel_payment_overlay_gateway" style="display: none;">
            <div class="rocket_fuel_payment_overlay_wrapper_gateway">
                <div id="rocketfuel_before_payment">
                    <div class="rocketfuel_process_payment">
                        <h3 class="indicate_text">Processing Payment</h3> <span>
                            <div class="loader_rocket"></div>
                        </span>
                    </div>
                </div>
                <div id="rocketfuel_retrigger_payment">
                    <button id="rocketfuel_retrigger_payment_button">
                        Resume
                    </button>
                    <div class="rocketfuel_exit_plan_wrapper">


                        <a onClick="RocketfuelPaymentEngine.showFinalOrderDetails()" class="proceed-forward-rkfl" style="display: flex;align-items: center;opacity:0.4">Go back
                            &nbsp; <figure style="display: flex;">
                                <img src="<?php echo esc_url(Plugin::get_url('assets/img/forward.svg')); ?>" alt="">
                            </figure>
                        </a>
                        <!-- <a onClick="RocketfuelPaymentEngine.showFinalOrderDetails()" class="completed-button-rkfl" style="display: flex;align-items: center;">Completed Payment ? </a> -->

                    </div>
                </div>
            </div>
        </div>
        </div>
        <!-- <script src="https://d3rpjm0wf8u2co.cloudfront.net/static/rkfl.js"></script> -->
        <script>
            /**
             * Payment Engine object
             */
            let RocketfuelPaymentEngine = {

                order_id: document.querySelector('input[name=rocket_order_id]').value,
                url: new URL(window.location.href),
                watchIframeShow: false,
                // uuid: RocketfuelPaymentEngine.url.searchParams.get("uuid"),
                getUUID: function() {
                    return this.url.searchParams.get("uuid");
                },
                getEnvironment: function() {
                    let environment = this.url.searchParams.get("env");

                    return environment || 'prod';
                },
                getUserData: function() {
                    let user_data = this.url.searchParams.get("user_data");

                    if (!user_data) return false;

                    let user_json = atob(user_data);

                    return JSON.parse(user_json);
                },
                updateOrder: function(result) {
                    try {



                        let rest_url = document.querySelector("input[name=rest_url]").value;

                        console.log("Response from callback :", result);

                        console.log("order_id :", RocketfuelPaymentEngine.order_id);

                        let status = "wc-on-hold";
                        let result_status = parseInt(result.status);
                        if (result_status == 101) {
                            status = "wc-partial-payment";
                        }
                        if (result_status == 1 || result.status == "completed") {
                            status = "admin_default"; //placeholder to get order status set by seller
                        }
                        if (result_status == -1) {
                            status = "wc-failed";
                        }
                        let fd = new FormData();
                        fd.append("order_id", RocketfuelPaymentEngine.order_id);
                        fd.append("status", status);
                        fetch(rest_url, {
                            method: "POST",
                            body: fd
                        }).then(res => res.json()).then(result => {
                            console.log(result)

                        }).catch(e => {
                            console.log(e)

                        })
                    } catch (error) {

                    }
                    RocketfuelPaymentEngine.showFinalOrderDetails();

                },
                showFinalOrderDetails: () => {
                    document.getElementById('rocket_fuel_payment_overlay_gateway').remove();
                },
                startPayment: function(autoTriggerState = true) {

                    if (!autoTriggerState) {
                        document.getElementById('rocketfuel_retrigger_payment_button').innerText = "Preparing Payment window...";
                        this.watchIframeShow = true;
                    }

                    document.getElementById('rocketfuel_retrigger_payment_button').disabled = true;

                    let checkIframe = setInterval(() => {

                        if (RocketfuelPaymentEngine.rkfl.iframeInfo.iframe) {
                            RocketfuelPaymentEngine.rkfl.initPayment();
                            clearInterval(checkIframe);
                        }

                    }, 500);

                },
                prepareRetrigger: function() {

                    //hide processing payment
                    document.getElementById('rocketfuel_before_payment').style.cssText = "visibility:hidden;height:0;width:0";

                    //show retrigger button
                    document.getElementById('rocketfuel_retrigger_payment_button').disabled = false;
                    document.getElementById('rocketfuel_retrigger_payment').style.display = "block";
                    // this.startPayment();
                },
                prepareProgressMessage: function() {

                    //show processing payment
                    document.getElementById('rocketfuel_before_payment').style.cssText = "visibility:visible;height:auto;width:auto";

                    //hide retrigger button
                    document.getElementById('rocketfuel_retrigger_payment_button').innerText = "Resume"; //revert trigger button message

                    document.getElementById('rocketfuel_retrigger_payment').style.display = "none";
                },

                windowListener: function() {
                    let engine = this;
                    window.addEventListener('message', (event) => {

                        switch (event.data.type) {
                            case 'rocketfuel_iframe_close':
                                if (document.getElementById('rocketfuel_before_payment'))
                                    engine.prepareRetrigger();
                                break;
                            case 'rocketfuel_new_height':
                                if (engine.watchIframeShow && document.getElementById('rocketfuel_before_payment')) {
                                    engine.prepareProgressMessage();
                                    engine.watchIframeShow = false;

                                }
                                break;
                            default:
                                break;
                        }

                    })
                },
                setLocalStorage: function(key, value) {
                    localStorage.setItem(key, value);
                },
                initRocketFuel: async function() {
                    return new Promise(async (resolve, reject) => {
                        if (!RocketFuel) {
                            location.reload();
                            reject();
                        }
                        let userData = RocketfuelPaymentEngine.getUserData();
                        let payload, response, rkflToken;

                        RocketfuelPaymentEngine.rkfl = new RocketFuel({
                            environment: RocketfuelPaymentEngine.getEnvironment()
                        });

                        if (userData.first_name && userData.email) {
                            payload = {
                                firstName: userData.first_name,
                                lastName: userData.last_name,
                                email: userData.email,
                                merchantAuth: userData.merchant_auth,
                                kycType: 'null',
                                kycDetails: {
                                    'DOB': "01-01-1990"
                                }
                            }


                            try {

                                if (userData.email !== localStorage.getItem('rkfl_email')) { //remove signon details when email is different
                                    localStorage.removeItem('rkfl_token');
                                    localStorage.removeItem('access');

                                }

                                rkflToken = localStorage.getItem('rkfl_token');

                                if (!rkflToken) {

                                    response = await RocketfuelPaymentEngine.rkfl.rkflAutoSignUp(payload, RocketfuelPaymentEngine.getEnvironment());

                                    // RocketfuelPaymentEngine.setLocalStorage('rkfl_first_name',userData.first_name);
                                    // RocketfuelPaymentEngine.setLocalStorage('rkfl_last_name',userData.last_name);
                                    RocketfuelPaymentEngine.setLocalStorage('rkfl_email', userData.email);

                                    if (response) {

                                        rkflToken = response.result?.rkflToken;

                                    }

                                }

                                const rkflConfig = {
                                    uuid: this.getUUID(),
                                    callback: RocketfuelPaymentEngine.updateOrder,
                                    environment: RocketfuelPaymentEngine.getEnvironment()
                                }
                                if (rkflToken) {
                                    rkflConfig.token = rkflToken;
                                }

                                console.log(rkflConfig);

                                RocketfuelPaymentEngine.rkfl = new RocketFuel(rkflConfig);

                                resolve(true);
                            } catch (error) {
                                reject();
                            }

                        }
                        resolve('no auto');
                    })

                },

                init: async function() {

                    let engine = this;
                    console.log('Start initiating RKFL');

                    try {

                        await engine.initRocketFuel();

                    } catch (error) {
                        console.log('error from promise', error)
                    }

                    console.log('Done initiating RKFL');

                    engine.windowListener();

                    if (document.getElementById('rocketfuel_retrigger_payment_button')) {
                        document.getElementById('rocketfuel_retrigger_payment_button').addEventListener('click', () => {
                            RocketfuelPaymentEngine.startPayment(false);
                        });

                    }

                    engine.startPayment();

                }
            }

            RocketfuelPaymentEngine.init();
        </script>

<?php
    }
    public static function add_gateway_class($methods)
    {
        $methods[] = 'Rocketfuel_Gateway\Controllers\Rocketfuel_Gateway_Controller';
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
        // $this->enqueue_action();
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
