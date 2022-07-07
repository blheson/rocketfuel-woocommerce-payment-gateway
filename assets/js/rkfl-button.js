(function ($, window, document) {
    'use strict';
    $(document).ready(() => {
        $('form.checkout').on('click', 'input[name="payment_method"]', function () {


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
    })


})(jQuery, window, document);