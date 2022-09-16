(function ($, window, document) {
    'use strict';
    $(document).ready(() => {

        let cacheDefaultPlaceOrder, count = 0;
        const RKFL_SELECTOR = '#rocketfuel_retrigger_payment_button';

        const intButn = setInterval(() => {

            try {
                if ($('#place_order') && $('#place_order').attr('style').includes('display:none') && !$(RKFL_SELECTOR).attr('style')) {
                    console.log("Force click triggered")

                    $('form.checkout input.input-radio')[0]?.click(); //force click
                }


            } catch (error) {
                console.error("Could not trigger place order", error?.message);
            }
            if (count > 10) {

                // buttonChangeObserver.observe(
                //     document.querySelector('#place_order'),
                //     {attributes: true}
                // );
                console.log("RKFL - clear btn check");

                clearInterval(intButn)
            }
            count++;
        }, 1000);
        // watch for 10secs

        $('form.checkout').on('click', 'input[name="payment_method"]', function () {

            var toggleRKFL, toggleSubmit;

            var isRKFLB = $(this).is('#payment_method_rocketfuel_gateway');
            if (isRKFLB) {
                toggleRKFL = 'show'
                toggleSubmit = 'hide'
          
                document.querySelector("#place_order").style.setProperty('visibility', 'hidden', true ? 'important' : '');
            } else {
                toggleRKFL = 'hide'
                toggleSubmit = 'show'
                document.querySelector("#place_order").style.removeProperty('visibility');

 
            }


            // if (isRKFLB) {

            //     if (!cacheDefaultPlaceOrder) {
            //         cacheDefaultPlaceOrder = $('#place_order')
            //     }
            //     console.log({ cacheDefaultPlaceOrder });

            //     sp.append(cacheDefaultPlaceOrder.html());

            //     // sp.classList.add('rocketfuel_wrapper_button');

            //     // $('#place_order').remove();//remove default


            //     .detach().appendTo
            //     console.log('custom', { isRKFLB });

            // } else {
            //     if ($('.rocketfuel_wrapper_button')) {
            //         $('.rocketfuel_wrapper_button').remove();//remove built
            //     }
            //     if ($('#place_order').length === 0) {
            //         $(RKFL_SELECTOR).parent().append(cacheDefaultPlaceOrder);
            //         console.log('default', { isRKFLB });
            //     }

            // }

            $(RKFL_SELECTOR).animate({
                opacity: toggleRKFL,
                height: toggleRKFL,
                padding: toggleRKFL
            }, 230);
            $('#place_order').animate({
                opacity: toggleSubmit,
                height: toggleSubmit,
                padding: toggleSubmit
            }, 230);

        });

    })

})(jQuery, window, document);
