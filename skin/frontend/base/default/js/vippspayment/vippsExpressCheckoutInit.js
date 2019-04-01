/**
 * Initialize VippsExpressCheckout on Action buttons.
 */
$(document).observe('dom:loaded', function () {
    $$('.vipps-express-checkout .vipps-checkout-action').each(function (button) {
        new VippsExpressCheckout(button);
    });
});
