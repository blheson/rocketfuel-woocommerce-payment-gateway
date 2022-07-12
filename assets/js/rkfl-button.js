(function ($, window, document) {
    'use strict';
    $(document).ready(() => {
        let count = 0;
        const intButn = setInterval(() => {
            
            try {
                if ($('#place_order').attr('style').includes('display:none') && !$('#rocketfuel_retrigger_payment_button').attr('style')) {
                    console.log("Force click triggered")
                    $('form.checkout input.input-radio')[0]?.click(); //force click
                }


            } catch (error) {
                console.error("Could not trigger place order", error?.message);
            }
            if(count>10){
                console.log("RKFL - clear btn check");

               clearInterval(intButn) 
            }
            count++;
        }, 1000); 
        // watch for 10secs

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
