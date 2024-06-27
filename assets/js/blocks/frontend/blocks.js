(() => {
    const RKFLBlockBasedObject = {
        userAgreeToPartialPayment: () => {
            return window.RocketfuelPaymentEngine.userAgreeToPartialPayment;
        },
        showError: function (message) {
            this.cart_resolve_object({ type: 'failure', message })
        },
        getUUID: async function (partial_tx_check = true) {
            const first_name = this.userData.first_name
            const last_name = this.userData.last_name
            const email = this.userData.email
            let url = wc_rkfl_context.start_block_checkout_url;
            const fd = `nonce=${wc_rkfl_context.start_checkout_nonce}&rkfl_checkout_firstname=${first_name}&rkfl_checkout_lastname=${last_name}&rkfl_checkout_email=${email}&rkfl_checkout_partial_tx_check=${partial_tx_check}`;
            let response = await fetch(url, {
                method: 'post',
                cache: 'no-cache',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: fd
            });
            let result = {};
            let rawresult = await response.text();
            if (rawresult) {
                try {
                    result = JSON.parse(rawresult);
                } catch (error) {
                    result.messages = ['Error parsing request'];
                    console.error(' ERROR_PARSE_GUUID', { error });
                }
            }
            if (!result.success) {
                var messages = result.data ? result.data.messages : result.messages;
                if (!messages) {
                    messages = 'Gateway request error';
                }
                this.showError(messages.toString());
                return null;

            }

            let uuid = result.data?.ext?.result?.uuid;

            if (!uuid) {
                //show error


                return false;
            }

        
            return { ...result.data, uuid }

        },
        paymentData: {
            status: "wc-on-hold"
        },

        update_order: function (result) {
            try {

             

                let status = "wc-on-hold";

                if (result?.status === undefined) {
                    return false;
                }

                let result_status = parseInt(result.status);

                if (result_status === 101) {
                    status = "wc-partial-payment";
                }

                if (result_status === 1) {

                    status = document.querySelector('input[name=payment_complete_order_status]')?.value || 'wc-processing';

                    //placeholder to get order status set by seller
                }

                if (result_status === -1) {
                    status = "wc-failed";
                }


                localStorage.setItem('payment_status_rocketfuel', 'complete');


                // document.getElementById('rocketfuel_retrigger_payment_button').style.display = 'none';

            } catch (error) {

                console.error('Error from update order method', error);

            }

        },

        initRocketFuel: async function () {
            let engine = this;

            return new Promise(async (resolve, reject) => {

                engine.window_listener();
                let data = await this.getUUID(); //set uuid
                let uuid = data.uuid;
                if (!uuid) {
                    reject();
                }
                if (data.isPartial) {

                    const userAgreed = await this.userAgreeToPartialPayment();
                    if (!userAgreed) {
                        data = await this.getUUID(); //set uuid
                        uuid = data.uuid;
                
                    }  
                }

                window.RKFLBlockBasedObject.access_token = data?.ext?.access_token;

                let userData = {
                    first_name: engine.userData.first_name,
                    last_name: engine.userData.last_name,
                    email: engine.userData.email,
                    merchant_auth: data.merchant_auth,
                    encrypted_req: data.encrypted_req
                };

                let  rkflToken;

                window.RKFLBlockBasedObject.rkfl = new RocketFuel({
                    environment: window.RKFLBlockBasedObject.environment
                });

                window.RKFLBlockBasedObject.rkflConfig = {
                    uuid,
                    callback: window.RKFLBlockBasedObject.update_order,
                    environment: window.RKFLBlockBasedObject.environment
                }
                if (userData.encrypted_req || (userData.first_name && userData.email)) {

                    const payload = {
                        encryptedReq: userData.encrypted_req,
                        merchantAuth: userData.merchant_auth,
                        email: userData.email,
                    }
                    try {
             

                        rkflToken = localStorage.getItem('rkfl_token');

                        if (!rkflToken && payload.merchantAuth) {
                            payload.accessToken = window.RKFLBlockBasedObject.access_token;
                            payload.isSSO = true;

                            const response = await window.RKFLBlockBasedObject.rkfl.rkflAutoSignUp(payload, window.RKFLBlockBasedObject.environment);

                            if (response) {
                                rkflToken = response.result?.rkflToken;
                            }
                        }

                        if (rkflToken) {
                            window.RKFLBlockBasedObject.rkflConfig.token = rkflToken;
                        }

                        resolve(true);
                    } catch (error) {
                        reject(error?.message);
                    }
                }

                if (window.RKFLBlockBasedObject.rkflConfig) {

                    window.RKFLBlockBasedObject.rkfl = new RocketFuel(window.RKFLBlockBasedObject.rkflConfig);
                    engine.startPayment();
                    // init RKFL
                    resolve(true);
                } else {
                    resolve(false);
                }
            })
        },
        environment: 'prod',
        cart_resolve_object: null,
        cart_reject_object: null,
        window_listener: function (resolve) {
            let engine = this;
            window.addEventListener('message', (event) => {
                switch (event.data.type) {
                    case 'rocketfuel_iframe_close':
                        if (event.data.paymentCompleted === 1) {
                            engine.cart_resolve_object({ type: 'success', message: 'Payment complete' });
                        } else {
                            engine.cart_resolve_object({ type: 'failure', message: 'Payment canceled' });
                        }
                        break;
                    case 'rocketfuel_new_height':
                    case 'rocketfuel_result_ok':
                        if (event.data.response) {
                       
                            engine.paymentResponse = event.data.response
                            engine.update_order(engine.paymentResponse);
                            // engine.cart_resolve_object();
                        }
                    default:
                        break;
                }
            })
        },
        startPayment: function () {
            const count = 0
            let checkIframe = setInterval(() => {

                if (count > 20) {
                    clearInterval(checkIframe);
                    return;
                }
                if (window.RKFLBlockBasedObject.rkfl.iframeInfo.iframe) {

                    window.RKFLBlockBasedObject.rkfl.initPayment();

                    clearInterval(checkIframe);
                }

            }, 500);

        },
        init: async function ({ resolve, reject, billing, environment }) {
            let engine = this;
            engine.cart_reject_object = (data) => { reject(data) };
            engine.cart_resolve_object = (data) => { resolve(data) };
            engine.environment = environment;
            engine.userData = {
                first_name: billing.billingData.first_name,
                last_name: billing.billingData.last_name,
                email: billing.billingData.email
            }
            return new Promise(async (resolve, reject) => {
                try {
                      await engine.initRocketFuel();
                } catch (error) {
                    engine.cart_resolve_object({type:'failure',message:'Error initiating payment'})
                }
                resolve()
            })

        }
    }

    window.RKFLBlockBasedObject = RKFLBlockBasedObject;
})();
(() => {
    "use strict";
    const { React, wc: { wcBlocksRegistry, wcSettings }, wp: { i18n, htmlEntities } } = window;

    const getTitle = ({ title }) => htmlEntities.decodeEntities(title) || (0, i18n.__)(
        "Rocketfuel",
        "woo-rocketfuel_gateway"
    );
 
    const getDescription = ({ description }) => htmlEntities.decodeEntities(description || "");
    const getContent = (data) => {
        const { onPaymentSetup } = data.eventRegistration;
        React.useEffect(() => {
            const onPaymentSetupUnsubscribe = onPaymentSetup(async () => {
                const result = await new Promise(async (resolve, reject) => {
                    window.RKFLBlockBasedObject.init({ resolve, reject, billing: data.billing, environment: data.environment });
                });
                return result
            });
            return onPaymentSetupUnsubscribe
        }, [onPaymentSetup])
        return htmlEntities.decodeEntities(data.description || "")
    };

    const gatewayData = wcSettings.getSetting("rocketfuel_gateway_data", {});

    // Get the translated title
    const title = getTitle({ title: gatewayData.title });
    const buttonText = getDescription({ description: gatewayData.button_text });

    // Define the payment method object
    const paymentMethod = {
        name: "rocketfuel_gateway",
        label: (0, React.createElement)((({ title }) => (0, React.createElement)(
            React.Fragment,
            null,
            (0, React.createElement)("div", {
                style: { display: "flex", flexDirection: "row", gap: "0.5rem" }
            },
                (0, React.createElement)("div", null, getTitle({ title })),
                // (0, React.createElement)(n, { label: getTitle({ title }) })
            ))),
            { title }),

        content: (0, React.createElement)(getContent, { description: gatewayData.description, environment: gatewayData.environment }),

        edit: React.createElement(getDescription, { description: gatewayData.description }),
        canMakePayment: (data) => { return true },
        ariaLabel: title,
        supports: {
            features: gatewayData.supports
        },
        placeOrderButtonLabel: buttonText
    };
    // wcBlocksRegistry.registerExpressPaymentMethod(paymentMethod);
    wcBlocksRegistry.registerPaymentMethod(paymentMethod);
})();