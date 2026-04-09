// Braintree payment gateway integration
// This script is loaded via wp_enqueue_script and receives the clientToken via wp_add_inline_script
(function() {
    var clientToken = (typeof window.emePgData !== 'undefined' && window.emePgData.braintree && window.emePgData.braintree.clientToken) ? window.emePgData.braintree.clientToken : '';
    if (!clientToken) {
        console.error('Braintree: No clientToken provided');
        return;
    }

    // Load Braintree Drop-in SDK dynamically
    var script = document.createElement('script');
    script.src = 'https://js.braintreegateway.com/web/dropin/1.33.0/js/dropin.min.js';
    script.async = true;
    script.onload = function() {
        var container = document.getElementById('braintree-payment-form-div');
        if (!container) {
            console.error('Braintree container #braintree-payment-form-div not found');
            return;
        }

        braintree.dropin.create({
            authorization: clientToken,
            container: container
        }, function(createErr, instance) {
            if (createErr) {
                console.error('Braintree create error:', createErr);
                return;
            }

            var submitButton = document.getElementById('braintree_submit_button');
            if (!submitButton) {
                console.error('Submit button #braintree_submit_button not found');
                return;
            }

            submitButton.addEventListener('click', function(event) {
                event.preventDefault();

                instance.requestPaymentMethod(function(err, payload) {
                    if (err) {
                        console.error('Request payment method error:', err);
                        return;
                    }

                    var nonceInput = document.getElementById('braintree_nonce');
                    if (nonceInput) {
                        nonceInput.value = payload.nonce;
                    }

                    var form = document.getElementById('eme_braintree_form');
                    if (form) {
                        form.submit();
                    } else {
                        console.error('Form #eme_braintree_form not found');
                    }
                });
            });
        });
    };
    script.onerror = function() {
        console.error('Failed to load Braintree Drop-in SDK');
    };
    document.head.appendChild(script);
})();
