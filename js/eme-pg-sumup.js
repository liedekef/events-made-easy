// SumUp payment gateway integration
// This script is loaded via wp_enqueue_script and receives the checkoutId via wp_add_inline_script
(function() {
    var checkoutId = (typeof window.emePgData !== 'undefined' && window.emePgData.sumup && window.emePgData.sumup.checkoutId) ? window.emePgData.sumup.checkoutId : '';
    if (!checkoutId) {
        console.error('SumUp: No checkoutId provided');
        return;
    }

    // Check if SumUpCard is available (loaded from https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js)
    if (typeof SumUpCard === 'undefined') {
        // Load the SumUp SDK if not already loaded
        var script = document.createElement('script');
        script.src = 'https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js';
        script.async = true;
        script.onload = function() {
            initSumUpCard(checkoutId);
        };
        script.onerror = function() {
            console.error('Failed to load SumUp SDK');
        };
        document.head.appendChild(script);
    } else {
        initSumUpCard(checkoutId);
    }

    function initSumUpCard(id) {
        SumUpCard.mount({
            checkoutId: id,
            onResponse: function(type, body) {
                if (type === 'success' || type === 'error') {
                    window.location.href = body.redirect_url;
                }
            }
        });
    }
})();
