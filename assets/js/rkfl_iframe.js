/**
 * Payment Engine object
 */
var RocketfuelPaymentEngine = {

    order_id: '',
    url: new URL(window.location.href),
    watchIframeShow: false,
    rkflConfig: null,

    getUUID: async function () {
        let uuid = document.querySelector('input[name=uuid_rocketfuel]').value;

        if (uuid) {
            return uuid;
        }

        let url = document.querySelector('input[name=admin_url_rocketfuel]').value;

        let response = await fetch(url);

        if (!response.ok) {
            return false;
        }

        let result = await response.json();

        if (!result.data?.result?.uuid) {
            return false;
        }

        RocketfuelPaymentEngine.order_id = result.data.temporary_order_id;

        document.querySelector('input[name=temp_orderid_rocketfuel]').value = result.data.temporary_order_id;

        console.log("res", result.data.result.uuid);

        return result.data.result.uuid;

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

            document.getElementById('rocketfuel_retrigger_payment_button').dataset.disable = true;

            document.getElementById('rocketfuel_retrigger_payment_button').style.opacity = 0.5;

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
        document.getElementById('rocketfuel_retrigger_payment_button').disabled = false;

        document.getElementById('rocketfuel_retrigger_payment_button').innerHTML = 'Pay with Rocketfuel';

    },
    prepareProgressMessage: function () {

        //revert trigger button message
        document.getElementById('rocketfuel_retrigger_payment_button').disabled = true;

    },

    windowListener: function () {
        let engine = this;

        window.addEventListener('message', (event) => {

            switch (event.data.type) {
                case 'rocketfuel_iframe_close':
                    engine.prepareRetrigger();
                    break;
                case 'rocketfuel_new_height':
                    engine.prepareProgressMessage();
                    engine.watchIframeShow = false;

                case 'rocketfuel_result_ok':

                    console.log('Event from rocketfuel_result_ok', event.data);

                    if (event.data.response) {
                        RocketfuelPaymentEngine.updateOrder(event.data.response);
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

        // if (document.getElementById('rocketfuel_retrigger_payment_button')) {

        //     document.getElementById('rocketfuel_retrigger_payment_button').addEventListener('click', (e) => {
        //         if (e.target.dataset.disable === 'true') {
        //             return;
        //         }
                // RocketfuelPaymentEngine.startPayment(false);

            // });

        // }

        engine.startPayment();

    }
}

document.querySelector(".rocketfuel_retrigger_payment_button").addEventListener('click', (e) => {
    e.preventDefault();
    if (e.target.dataset.disable === 'true') {
        return;
    }

    document.getElementById('rocketfuel_retrigger_payment_button').innerHTML = '<div class="loader_rocket"></div>';

    RocketfuelPaymentEngine.init();

})
