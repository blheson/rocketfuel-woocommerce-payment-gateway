<?php

namespace Rocketfuel_Gateway\Controllers;

use Rocketfuel_Gateway\Plugin;

class Rocketfuel_Gateway_Controller extends \WC_Payment_Gateway
{
	public function __construct()
	{
		$this->id = 'rocketfuel_gateway';

		// $this->icon = 'rkfl.png';

		$this->has_fields = false;

		$this->method_title = 'Rocketfuel';

		$this->method_description = 'Pay with Crypto using Rocketfuel';

		$this->init_form_fields();

		$this->init_settings();

		$this->title = $this->get_option('title');


		$this->environment = $this->get_option('environment');

		$this->endpoint = $this->get_endpoint($this->environment);

		$this->public_key = $this->get_option('public_key');

		$this->description = $this->get_option('description');

		$this->password = $this->get_option('password');

		$this->email = $this->get_option('email');

		$this->payment_complete_order_status = $this->get_option('payment_complete_order_status') ? $this->get_option('payment_complete_order_status') : 'completed';

		$this->supports = array('products');

		$this->merchant_id = $this->get_option('merchant_id');

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	}
	public function get_endpoint($environment){
		
		$environment_data = array(
			'prod' => 'https://app.rocketfuelblockchain.com/api',
			'dev' => 'https://dev-app.rocketdemo.net/api',
			'stage2' => 'https://qa-app.rocketdemo.net/api',
			'preprod' => 'https://preprod-app.rocketdemo.net/api',
		);

		return isset($environment_data[$environment]) ? $environment_data[$environment] : 'https://app.rocketfuelblockchain.com/api';
	}
	public function init_form_fields()
	{
		$all_wc_order_status = wc_get_order_statuses();

		$this->form_fields = apply_filters('rocketfuel_admin_fields', array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'rocketfuel-payment-gateway'),
				'type' => 'checkbox',
				'label' => __('Enable Rocketfuel', 'rocketfuel-payment-gateway'),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __('Title', 'rocketfuel-payment-gateway'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'rocketfuel-payment-gateway'),
				'default' => __('Rocketfuel', 'rocketfuel-payment-gateway'),
				'desc_tip'      => true,
			),

			'environment' => array(
				'title' => __('Working environment', 'rocketfuel-payment-gateway'),
				'type' => 'select',
				'default' => 'prod',
				'options' =>  array(
					'prod' => 'Production',
					'dev' => 'Development',
					'stage2' => 'QA',
					'preprod' => 'Pre-Production'
				)
			),
			'description' => array(
				'title' => __('Customer Message', 'rocketfuel-payment-gateway'),
				'type' => 'textarea',
				'default' => 'Pay for your order with RocketFuel'
			),
			'merchant_id' => array(
				'title' => __('Merchant ID', 'rocketfuel-payment-gateway'),
				'type' => 'text',
				'default' => ''
			),
			'public_key' => array(
				'title' => __('Public Key', 'rocketfuel-payment-gateway'),
				'type' => 'textarea',
				'default' => ''
			), 'email' => array(
				'title' => __('Email', 'rocketfuel-payment-gateway'),
				'type' => 'text',
				'default' => ''
			), 'password' => array(
				'title' => __('Password', 'rocketfuel-payment-gateway'),
				'type' => 'password',
				'default' => ''
			),
			'payment_complete_order_status' => array(
				'title' => __('Order Status for Completed Payment', 'rocketfuel-payment-gateway'),
				'type' => 'select',
				'default' => 'wc-completed',
				'options' =>  $all_wc_order_status
			),
			'callback_url' => array(
				'title' => __('Callback URL', 'rocketfuel-payment-gateway'),
				'type' => 'checkbox',
				'label' => esc_url(rest_url() . Plugin::get_api_route_namespace() . '/payment'),
				'description' => __('Callback URL for Rocketfuel', 'rocketfuel-payment-gateway'),
				'default' => '',
				'css' => 'display:none'
				// 'desc_tip'      => true,
			)
		));
	}
	public function payment_fields()
	{
		global $woocommerce;
		if (!$this->password || !$this->email) {
			echo '<span style="color:red">' . __('Vendor should fill in the settings page to start using Rocketfuel', 'rocketfuel') . '</span>';
			return;
		}



?>
		<style>
			.rocketfuel_process_payment {
				text-align: center;
				display: flex;
				justify-content: center;
				align-content: center;
				align-items: baseline;
			}

			#Rocketfuel {
				display: block;
				position:fixed
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

			.rocketfuel_retrigger_payment_button {
				padding: 10px;
				background: #983612;
				color: #fff;
				cursor: pointer;
			}
		</style>

		<div>
			<span id="rocketfuel_retrigger_payment_button" class="rocketfuel_retrigger_payment_button">Click to pay</space>
		</div>

		<input type="hidden" name="admin_url_rocketfuel" value="<?php echo admin_url('admin-ajax.php?action=rocketfuel_process_user_data&nonce=' . wp_create_nonce("rocketfuel_nonce")) ?>">
		<script>
			// (() => {
			/**
			 * Payment Engine object
			 */
			var RocketfuelPaymentEngine = {

				order_id: (new Date).getTime(),
				url: new URL(window.location.href),
				watchIframeShow: false,
				// uuid: RocketfuelPaymentEngine.url.searchParams.get("uuid"),
				getUUID: async function() {
					let url = document.querySelector('input[name=admin_url_rocketfuel]').value;
					let response = await fetch(url);
					if (!response.ok) {
						return false;
					}
					let result = await response.json();
					if (!result.data?.result?.uuid) {
						return false;
					}
					// console.log("res", result.data.result.uuid);
					return result.data.result.uuid;
				},
				getEnvironment: function() {
					let environment = "<?php echo $this->environment; ?>"

					return environment || 'prod';
				},
				getUserData: function() {
					// let user_data = this.url.searchParams.get("user_data");

					// admin_url_rocketfuel
					// const request = new Request(url);

					// const init = {
					// 	method: 'GET',
					// };


					let user_data = {
						first_name: 'per',
						last_name: 'per',
						email: 'e@f.com'
					}

					if (!user_data) return false;

					// let user_json = atob(user_data);

					// return JSON.parse(user_json);
					return user_data;
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
					// RocketfuelPaymentEngine.showFinalOrderDetails();

				},
				showFinalOrderDetails: () => {
					// document.getElementById('rocket_fuel_payment_overlay_gateway').remove();
				},
				startPayment: function(autoTriggerState = true) {


					// document.getElementById('rocketfuel_retrigger_payment_button').innerText = "Preparing Payment window...";
					this.watchIframeShow = true;


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
					// document.getElementById('rocketfuel_before_payment').style.cssText = "visibility:hidden;height:0;width:0";

					//show retrigger button
					document.getElementById('rocketfuel_retrigger_payment_button').disabled = false;
					// document.getElementById('rocketfuel_retrigger_payment').style.display = "block";

				},
				prepareProgressMessage: function() {

					//show processing payment
					// document.getElementById('rocketfuel_before_payment').style.cssText = "visibility:visible;height:auto;width:auto";

					//hide retrigger button
					document.getElementById('rocketfuel_retrigger_payment_button').innerText = "Resume"; //revert trigger button message

					document.getElementById('rocketfuel_retrigger_payment').style.display = "none";
				},

				windowListener: function() {
					let engine = this;
					window.addEventListener('message', (event) => {

						switch (event.data.type) {
							case 'rocketfuel_iframe_close':
								engine.prepareRetrigger();
								break;
							case 'rocketfuel_new_height':
								// engine.prepareProgressMessage();
								engine.watchIframeShow = false;
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

								if (!rkflToken && payload.merchantAuth) {

									response = await RocketfuelPaymentEngine.rkfl.rkflAutoSignUp(payload, RocketfuelPaymentEngine.getEnvironment());

									// RocketfuelPaymentEngine.setLocalStorage('rkfl_first_name',userData.first_name);
									// RocketfuelPaymentEngine.setLocalStorage('rkfl_last_name',userData.last_name);
									RocketfuelPaymentEngine.setLocalStorage('rkfl_email', userData.email);

									if (response) {

										rkflToken = response.result?.rkflToken;

									}

								}

								let uuid = await this.getUUID();
								const rkflConfig = {
									uuid,
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

						let res = await engine.initRocketFuel();
						console.log(res);
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


			document.querySelector(".rocketfuel_retrigger_payment_button").addEventListener('click', (e) => {
				e.preventDefault();
				console.log('clicked');
				RocketfuelPaymentEngine.init();
			})
			// })
		</script>
<?php
	}
	/**
	 * Parse cart items and prepare for order
	 * @param array $items 
	 * @return array
	 */
	public function sortCart($items)
	{
		$data = array();

		file_put_contents(__DIR__ . "/cart.json", "item " . json_encode($items));

		foreach ($items as $cart_item) {
			$data[] = array(
				'name' => $cart_item['data']->get_title(),
				'id' => (string)$cart_item['product_id'],
				'price' => $cart_item['data']->get_price(),
				'quantity' => (string)$cart_item['quantity']
			);
		}

		if((null !== WC()->cart->get_shipping_total()) && (!strpos(strtolower(WC()->cart->get_shipping_total()), 'free'))){

			$data[] = array(
				'name' => 'Shipping',
				'id' => microtime(),
				'price'=> WC()->cart->get_shipping_total(),
				'quantity' => '1'
			);

		}
		
		return $data;
	}
	/**
	 * Update order when payment has been confirmed
	 * @param WP_REST_REQUEST $request_data
	 * @return void
	 */
	public function update_order($request_data){

		$data = $request_data->get_params();

		if (!$data['order_id'] || !$data['status']) {

			echo json_encode(array('status' => 'failed', 'message' => 'Order was not updated. Invalid parameter. You must pass in order_id and status'));

			exit;

		}

		$order_id = $data['order_id'];

		$status = $data['status'];

		$order = wc_get_order($order_id);

		if (!$order) {
			echo json_encode(array('status' => 'failed', 'message' => 'Order was not updated. Could not retrieve order from the order_id that was sent'));
			exit;
		}

		if ($status === 'admin_default')
			$status = $this->payment_complete_order_status;

		$data = $order->update_status($status);
		echo json_encode(array('status' => 'success', 'message' => 'Order was updated'));
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

		$user_data = base64_encode(json_encode(array(
			'first_name' => $order->get_billing_first_name(),
			'last_name' => $order->get_billing_last_name(),
			'email' => $order->get_billing_email(),
			'merchant_auth' => 	$this->merchant_auth()
		)));
		$merchant_cred = array(
			'email' => $this->email,
			'password' => $this->password
		);
		$data = array(
			'cred' => $merchant_cred,
			'endpoint' => $this->endpoint,
			'body' => array(
				'amount' => $order->get_total(),
				'cart' => $cart,
				'merchant_id' => $this->merchant_id,
				'currency' => $order->get_currency(),
				'order' => (string) $order_id,
				'redirectUrl' => ''
			)
		);

		$payment_response = Process_Payment_Controller::process_payment($data);

		if (!$payment_response) {
			return;
		}

		$result = json_decode($payment_response);

		if (!isset($result->result) && !isset($result->result->url)) {
			wc_add_notice(__('Failed to place order', 'rocketfuel-payment-gateway'), 'error');
			return false;
		}
		$urlArr = explode('/', $result->result->url);
		$uuid = $urlArr[count($urlArr) - 1];

		// Remove cart
		$woocommerce->cart->empty_cart();
		// Return thankyou redirect
		// $pay_link = get_permalink(get_option(Plugin::$prefix . 'process_payment_page_id' ));
		// $order_key = explode( 'order-received', $this->get_return_url($order))[1];
		$buildUrl = $this->get_return_url($order) . "&uuid=" . $uuid . "&user_data=" . $user_data;

		if ($this->environment !== 'prod') {

			$buildUrl .= '&env=' . $this->environment;
		}

		return array(
			'result' => 'success',
			'redirect' => $buildUrl
		);
	}
	public function merchant_auth()
	{
		return $this->get_encrypted($this->merchant_id);
	}
	/**
	 * Encrypt Data
	 *
	 * @param $to_crypt string to encrypt
	 * @return string
	 */
	public function get_encrypted($to_crypt)
	{

		$out = '';

		$pub_key_path = dirname(__FILE__) . '/rf.pub';

		if (!file_exists($pub_key_path)) {
			return false;
		}
		$cert = file_get_contents($pub_key_path);

		$public_key = openssl_pkey_get_public($cert);

		$key_lenght = openssl_pkey_get_details($public_key);

		$part_len = $key_lenght['bits'] / 8 - 11;
		$parts = str_split($to_crypt, $part_len);
		foreach ($parts as $part) {
			$encrypted_temp = '';
			openssl_public_encrypt($part, $encrypted_temp, $public_key, OPENSSL_PKCS1_OAEP_PADDING);
			$out .=  $encrypted_temp;
		}

		return base64_encode($out);
	}
}
