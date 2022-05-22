; (function ($, window, document) {
    'use strict';

    // if (document.getElementById('place_order'))
    //     document.getElementById('place_order').style.display = 'none';
    var selector = '#rocketfuel_retrigger_payment_button';
    /**
     * Payment Engine object
     */
    var RocketfuelPaymentEngine = {

        order_id: '',
        url: new URL(window.location.href),
        watchIframeShow: false,
        rkflConfig: null,
        paymentResponse: '',
        // Show error notice at top of checkout form, or else within button container
        showError: function (errorMessage, selector) {
            var $container = $('.woocommerce-notices-wrapper, form.checkout');

            if (!$container || !$container.length) {
                $(selector).prepend(errorMessage);
                return;
            } else {
                $container = $container.first();
            }

            // Adapted from https://github.com/woocommerce/woocommerce/blob/ea9aa8cd59c9fa735460abf0ebcb97fa18f80d03/assets/js/frontend/checkout.js#L514-L529
            $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
            $container.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + errorMessage + '</div>');
            $container.find('.input-text, select, input:checkbox').trigger('validate').trigger('blur');

            var scrollElement = $('.woocommerce-NoticeGroup-checkout');
            if (!scrollElement.length) {
                scrollElement = $container;
            }

            if ($.scroll_to_notices) {
                $.scroll_to_notices(scrollElement);
            } else {
                // Compatibility with WC <3.3
                $('html, body').animate({
                    scrollTop: ($container.offset().top - 100)
                }, 1000);
            }

            $(document.body).trigger('checkout_error');
        }
        ,
        getUUID: async function () {


            let firstname = document.getElementById('shipping_first_name')?.value;
            let lastname = document.getElementById('shipping_last_name')?.value;

            let url = wc_rkfl_context.start_checkout_url;
            // let url = document.querySelector('input[name=admin_url_rocketfuel]').value;

            if (lastname) {
                url += '&shipping_lastname=' + lastname;
            }
            if (firstname) {
                url += '&shipping_firstname=' + firstname;
            }
            var data = $('form.checkout')
                .add($('<input type="hidden" name="nonce" /> ')
                    .attr('value', wc_rkfl_context.start_checkout_nonce)
                )
                .serialize();
            let response = await fetch(url, {
                method: 'post',
                cache: 'no-cache',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: data
            });

            let rawresult = await response.text();

            let result = JSON.parse(rawresult);

            if (!result.success) {

                document.getElementById('rocketfuel_retrigger_payment_button').innerHTML = 'Pay with Rocketfuel';

                // Error messages may be preformatted in which case response structure will differ
                var messages = result.data ? result.data.messages : result.messages;

                console.log("Messages from start checkout", messages);

                if ('string' === typeof messages) {
                    this.showError(messages);
                } else {
                    var messageItems = messages.map(function (message) {
                        return '<li>' + message + '</li>';
                    }).join('');
                    this.showError('<ul class="woocommerce-error" role="alert">' + messageItems + '</ul>', selector);
                }

                return null;
                
            }

            let uuid = result.data?.uuid?.result?.uuid;

            if (!uuid) {
                return false;
            }


            RocketfuelPaymentEngine.order_id = result.data.temporary_order_id;

            document.querySelector('input[name=temp_orderid_rocketfuel]').value = result.data.temporary_order_id;

            console.log("res", uuid);

            return uuid;

        },
        getEnvironment: function () {
            let environment = document.querySelector('input[name=environment_rocketfuel]')?.value;

            return environment || 'prod';
        },
        getUserData: function () {

            let user_data = {

                first_name: document.getElementById('billing_first_name') ? document.getElementById('billing_first_name').value : null,

                last_name: document.getElementById('billing_last_name') ? document.getElementById('billing_last_name').value : null,

                email: document.getElementById('billing_email') ? document.getElementById('billing_email').value : null,

                merchant_auth: document.querySelector('input[name=merchant_auth_rocketfuel]') ? document.querySelector('input[name=merchant_auth_rocketfuel]').value : null
            }

            if (!user_data) return false;

            return user_data;

        },
        triggerPlaceOrder: function () {
            // document.getElementById('place_order').style.display = 'inherit';
            console.log('Trigger is calling');

            $('form.checkout').trigger('submit');

            // document.getElementById('place_order').style.display = 'none';

            console.log('Trigger has neen called ');
        },
        updateOrder: function (result) {
            try {

                console.log("Response from callback :", result, result?.status === undefined);
         

                let status = "wc-on-hold";

                if (result?.status === undefined) {
                    return false;
                }

                let result_status = parseInt(result.status);

                if (result_status === 101) {
                    status = "wc-partial-payment";
                }

                if (result_status === 1 || result.status === "completed") {

                    status = document.querySelector('input[name=payment_complete_order_status]')?.value || 'wc-processing';

                    //placeholder to get order status set by seller
                }

                if (result_status === -1) {
                    status = "wc-failed";
                }

                document.querySelector('input[name=order_status_rocketfuel]').value = status;

                document.querySelector('input[name=payment_status_rocketfuel]').value = 'complete';

                localStorage.setItem('payment_status_rocketfuel', 'complete');

                document.getElementById('rocketfuel_retrigger_payment_button').dataset.disable = true;

                document.getElementById('rocketfuel_retrigger_payment_button').style.opacity = 0.5;

                // document.getElementById('rocketfuel_retrigger_payment_button').style.display = 'none';

            } catch (error) {

                console.error('Error from update order method', error);

            }

        },

        startPayment: function (autoTriggerState = true) {

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
        prepareRetrigger: function () {

            //show retrigger button
            document.getElementById('rocketfuel_retrigger_payment_button').dataset.disable = false;


            document.getElementById('rocketfuel_retrigger_payment_button').innerHTML = 'Pay with Rocketfuel';

        },
        prepareProgressMessage: function () {

            //revert trigger button message

            document.getElementById('rocketfuel_retrigger_payment_button').dataset.disable = true;


        },

        windowListener: function () {
            let engine = this;

            window.addEventListener('message', (event) => {
            
                switch (event.data.type) {
                    case 'rocketfuel_iframe_close':
                        console.log('Event from rocketfuel_iframe_close', event.data);


                        // engine.prepareRetrigger();
                        document.getElementById('rocketfuel_retrigger_payment_button').style.opacity = 1;
                        
                        if (event.data.paymentCompleted === 1) {
                            engine.triggerPlaceOrder();
                        } else {
                            engine.prepareRetrigger();
                        }
                        break;
                    case 'rocketfuel_new_height':
                        engine.prepareProgressMessage();

                        engine.watchIframeShow = false;

                        document.getElementById('rocketfuel_retrigger_payment_button').innerHTML = 'Pay with Rocketfuel';
                        document.getElementById('rocketfuel_retrigger_payment_button').style.opacity = 0.5;


                    case 'rocketfuel_result_ok':



                        if (event.data.response) {

                            console.log('Payment response has been recorded');

                            engine.paymentResponse = event.data.response
                            
                            engine.updateOrder(engine.paymentResponse);

                        }

                    default:
                        break;
                }

            })
        },
        setLocalStorage: function (key, value) {
            localStorage.setItem(key, value);
        },
        initRocketFuel: async function () {

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

                let uuid = await this.getUUID(); //set uuid
                if (!uuid) {
                    return;
                }
                RocketfuelPaymentEngine.rkflConfig = {
                    uuid,
                    callback: RocketfuelPaymentEngine.updateOrder,
                    environment: RocketfuelPaymentEngine.getEnvironment()
                }

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
                        console.log('details', userData.email, localStorage.getItem('rkfl_email'), payload);

                        if (userData.email !== localStorage.getItem('rkfl_email')) { //remove signon details when email is different
                            localStorage.removeItem('rkfl_token');
                            localStorage.removeItem('access');

                        }

                        rkflToken = localStorage.getItem('rkfl_token');

                        if (!rkflToken && payload.merchantAuth) {

                            response = await RocketfuelPaymentEngine.rkfl.rkflAutoSignUp(payload, RocketfuelPaymentEngine.getEnvironment());


                            RocketfuelPaymentEngine.setLocalStorage('rkfl_email', userData.email);

                            if (response) {

                                rkflToken = response.result?.rkflToken;

                            }

                        }


                        if (rkflToken) {
                            RocketfuelPaymentEngine.rkflConfig.token = rkflToken;
                        }

                        resolve(true);
                    } catch (error) {
                        reject(error?.message);
                    }

                }

                if (RocketfuelPaymentEngine.rkflConfig) {

                    RocketfuelPaymentEngine.rkfl = new RocketFuel(RocketfuelPaymentEngine.rkflConfig); // init RKFL
                    resolve(true);

                } else {
                    resolve(false);
                }

            })

        },

        init: async function () {

            let engine = this;
            console.log('Start initiating RKFL');

            try {

                let res = await engine.initRocketFuel();
                console.log(res);

            } catch (error) {

                console.log('error from promise', error);

            }

            console.log('Done initiating RKFL');

            engine.windowListener();


            engine.startPayment();

        }
    }

    // document.querySelector("")
    
    document.querySelector(".rocketfuel_retrigger_payment_button").addEventListener('click', (e) => {
        e.preventDefault();

        if (e.target.dataset.disable === 'true') {
            return;
        }

        document.getElementById('rocketfuel_retrigger_payment_button').innerHTML = '<div class="loader_rocket"></div>';

        RocketfuelPaymentEngine.init();

    })
    document.querySelector('input[name=payment_status_rocketfuel]').value = localStorage.getItem('payment_status_rocketfuel');


})(jQuery, window, document);

