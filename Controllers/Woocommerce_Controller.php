<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

class Woocommerce_Controller
{
    public static function register()
    {
        add_action('plugins_loaded', array(__CLASS__, 'init_rocketfuel_gateway_class'));
        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway_class'));

        add_action('init', array(__CLASS__, 'register_partial_payment_order_status'));
        add_action('woocommerce_thankyou', array(__CLASS__, 'administer_thank_you_page'));
        add_filter('wc_order_statuses', array(__CLASS__, 'add_partial_payment_to_order_status'));
    }
    public static function administer_thank_you_page($order_id)
    {

?>

        <style>
            main{
                display: none;
            }
            .rocketfuel_process_payment {
                text-align: center;
                display: flex;
    justify-content: center;
    align-content: center;
    align-items: center;
            }

            #rocketfuel_process_payment_button {
                background-color: #229633;
                border: #229633;
            }

            /* header.entry-header,h1{
              display:none
          } */
            h3.indicate_text {
                /* font-weight: 800; */
                font-size: 2rem;
                margin-right:10px
            }

            .loader {
              
    border: 1px solid #171616;
    border-top: 1px solid #b9b9b9;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    animation: spin 0.4s linear infinite;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }
        </style>

   
        <input type="hidden" name="rocket_order_id" value="<?php echo $order_id?>"> 
        <input type="hidden" name="rest_url" value="<?php echo   rest_url() . Plugin::get_api_route_namespace() . '/update_order'?>">
        <script src="https://d3rpjm0wf8u2co.cloudfront.net/static/rkfl.js"></script>
        <script>
            (() => {
              
                let url = new URL(window.location.href);
     
                let uuid = url.searchParams.get("uuid");
                const order_id = document.querySelector('input[name=rocket_order_id]').value
               
                rkfl = new RocketFuel({
                    uuid,
                    callback: updateOrder,
                    environment: "prod"
                });

                function updateOrder(result) {
                    let rest_url = document.querySelector("input[name=rest_url]").value
                    console.log("Response from callback :", result);
                    // console.log("order_id :", order_id);

                    let status = "on-hold";
                    let result_status = parseInt(result.status);
                    if (result_status == 101) {
                        status = "partial-payment";
                    }
                    if (result_status == 1 || result.status == "completed") {
                        status = "completed";
                    }
                    if (result_status == -1) {
                        status = "failed";
                    }
                    let fd = new FormData();
                    fd.append("order_id", order_id);
                    fd.append("status", status);
                    fetch(rest_url, {
                        method: "POST",
                        body: fd
                    }).then(res => res.json()).then(result => {
                        console.log(result)
                   
                    }).catch(e => {
                        console.log(e)
                     
                    })
                    showFinalOrderDetails();
                    // window.location.replace(rocketfuel_redir);
                }

                function showFinalOrderDetails() {
                    document.querySelector('main').style.display = 'block';
                    document.getElementById('rocketfuel_before_payment').remove();
                }

                function startPayment() {
                    rkfl.initPayment();
                }


                document.addEventListener("DOMContentLoaded", function() {
                    setTimeout(() => {
                        startPayment();
                    }, 3500)

                });

                function createPaymentDiv() {
                    let div = document.createElement('div');
                    div.innerHTML = `<div class="rocketfuel_process_payment"> <h3 class="indicate_text">Processing Payment</h3> <span style="margin-top: 7px;"><div class="loader"></div></span> </div> </div>`;
                    div.id = "rocketfuel_before_payment";
                    return div;
                }

                function init() {
                    // document.querySelector("button#rocketfuel_process_payment_button").addEventListener("click", function() {
                    //     this.innerText = "Processing.. Please wait";
                    //     this.disabled = true;
                    //     this.opacity = 0.5
                    //     startPayment();
                    // })

                    document.querySelector('#primary').appendChild(createPaymentDiv());
                    document.querySelector('main').style.display = 'none';

                }
                init();
            })()
        </script>
<?php
    }
    public static function add_gateway_class($methods)
    {
        $methods[] = 'Rocketfuel_Gateway\Controllers\Rocketfuel_Gateway_Controller';
        return $methods;
    }
    public static function init_rocketfuel_gateway_class()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }
        require_once('Rocketfuel_Gateway_Controller.php');
    }
    public static function register_partial_payment_order_status()
    {
        $args = array(
            'label' => 'Partial payment',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Partial Payment <span class="count">(%s)</span>', 'Partial Payments <span class="count">(%s)</span>')
        );
        register_post_status('wc-partial-payment', $args);
    }
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
