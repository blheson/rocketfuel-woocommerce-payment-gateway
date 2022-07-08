(function ($, window, document) {
    'use strict';
    $(document).ready(() => {

        $('form.checkout').on('click', 'input[name="payment_method"]', function () {
            console.log("RKFL click is initiated")

            var isPPEC = $(this).is('#payment_method_rocketfuel_gateway');
            var toggleRKFL = isPPEC ? 'show' : 'hide';
            var toggleSubmit = isPPEC ? 'hide' : 'show';


            $('#rocketfuel_retrigger_payment_button').animate({
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
        setTimeout(() => {
            try {
                if ($('#place_order').attr('style').includes('display:none') && !$('#rocketfuel_retrigger_payment_button').attr('style')) {
                    $('form.checkout input.input-radio')[0]?.click(); //force click
                }


            } catch (error) {
                console.error("Could not trigger place order", error?.message)
            }
        }, 500)


    })

})(jQuery, window, document);
