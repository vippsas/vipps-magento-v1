var VippsExpressCheckout = Class.create();
/**
 * Add Vipps express checkout parameter when adds product to cart.
 * @type {{buttonObserver: VippsExpressCheckout.buttonObserver, getAddToCartForm: VippsExpressCheckout.getAddToCartForm, initialize: VippsExpressCheckout.initialize}}
 */
VippsExpressCheckout.prototype = {
    /**
     *
     * @param button
     */
    initialize: function (button) {
        if(typeof button != 'undefined') {
            this.button = $(button);
            var handler;
            if(this.button.readAttribute('data-is-product')) {
                handler = this.productViewHandler.bindAsEventListener(this);
            } else {
                handler = this.cartHandler;
            }
            this.button.observe('click', handler);
        }
    },

    /**
     * Resolve Addtocart form
     * @returns {*}
     */
    getAddToCartForm: function () {
        if (('undefined' != typeof productAddToCartFormOld) && productAddToCartFormOld) {
            return productAddToCartFormOld
        }
        if (('undefined' != typeof productAddToCartForm) && productAddToCartForm) {
            return productAddToCartForm
        }

    },

    cartHandler: function(){
        Event.stop(event);
        setLocation(this.readAttribute('data-redirect-url'));
    },

    /**
     *
     * @param event
     */
    productViewHandler: function (event) {
        Event.stop(event);
        var productAddToCartForm = this.getAddToCartForm();
        if (!productAddToCartForm) {
            alert('productAddToCartForm form is not defined');
        }
        if (productAddToCartForm.validator.validate()) {

            // Insert hidden field to run redirection after product has been added add to cart.
            var expressCheckoutInitiator = new Element(
                'input',
                {type: 'hidden', name: 'vipps_express_checkout', value: '1'})
            ;

            productAddToCartForm.form.insert({top: expressCheckoutInitiator});
            productAddToCartForm.submit();
        }
    }
};

$(document).observe('dom:loaded', function () {

    $$('.vipps-express-checkout .vipps-checkout').each(function(button){
        new VippsExpressCheckout(button);
    });
});
