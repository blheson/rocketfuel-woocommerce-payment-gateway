<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;
use Rocketfuel_Gateway\Helpers\View;

class Shortcode_Controller
{

    public static function register()
    {
        self::register_shortcode();
    }
    /**
     * Register shortcode for payment gateway
     */
    public static function register_shortcode()
    {
        if (is_admin() && (defined('DOING_AJAX') && DOING_AJAX))
            return false;

        add_shortcode('rocketfuel_process_payment', array(__CLASS__, 'render_payment_page'));
    }
    /**
     * Set up payment page
     * @return string
     */
    public static function render_payment_page()
    {
        return '<style>
          .rocketfuel_process_payment {
              text-align: center;
          }

          #rocketfuel_process_payment_button {
              background-color: #229633;
              border: #229633;
          }
          header.entry-header,h1{
              display:none
          }
          h3.indicate_text{
            font-weight: 800;
            font-size:2rem
        }
      </style>

      <div class="rocketfuel_process_payment">
          <input type="hidden" name="redirect_to_shop" value="' . site_url() . '">
          <input type="hidden" name="rest_url" value="' .  rest_url() . Plugin::get_api_route_namespace() . '/update_order">
         <div style="display:none">
            <div> 
                <small>Your order is currently pending. Proceed with payment to complete order</small> 
            </div>
            <div>
                <button id="rocketfuel_process_payment_button">
                    Pay With Rocketfuel
                </button>
            </div>
        </div>           
          <div><h3 class="indicate_text">Processing Payment...</h3></div>

      </div>
      <script src="https://d3rpjm0wf8u2co.cloudfront.net/static/rkfl.js"></script>
      <script>
          (() => {
              let site_url = document.querySelector("input[name=redirect_to_shop]").value;
              let url = new URL(window.location.href);
              let redirect = url.searchParams.get("redirect");
              if (!redirect) {
                  let shop_redirect = `${site_url}/shop`;
                  window.location.href = shop_redirect;
              }
            //   console.log(`${site_url}/checkout/order-received${redirect}`);
              // /checkout/order-received+redirect
              let uuid= url.searchParams.get("uuid");
              const order_id= url.searchParams.get("order_id");
const rocketfuel_redir=`${site_url}/checkout/order-received${redirect}`;
rkfl = new RocketFuel({
    uuid,
    callback:  updateOrder,
    environment:  "prod"
});
        function updateOrder(result){
            let rest_url = document.querySelector("input[name=rest_url]").value
            console.log("Response from callback :", result);
            // console.log("order_id :", order_id);

            let status = "on-hold";
            let result_status = parseInt(result.status);
            if(result_status == 101){
                status = "partial-payment";
            }
            if(result_status == 1 || result.status == "completed"){
                status = "completed";
            }
            if(result_status == -1){
                status = "failed";
            }
            let fd = new FormData();
            fd.append("order_id",order_id);
            fd.append("status",status);
            fetch(rest_url,{
                method:"POST",
                body:fd
            }).then(res=>res.json()).then(result=>{
                console.log(result)
            }).catch(e=>{
                console.log(e)
            })
            window.location.replace(rocketfuel_redir); 
        }
        function startPayment(){
        rkfl.initPayment();
        }


        document.addEventListener("DOMContentLoaded",function(){
        setTimeout(()=>{  startPayment();},3500)

        });
        document.querySelector("button#rocketfuel_process_payment_button").addEventListener("click",function(){
        this.innerText = "Processing.. Please wait";
        this.disabled = true;
        this.opacity=0.5
        startPayment();
        })
          })()
      </script>';
    }
}
