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
        this.options = $H({isProduct: '0', redirectUrl: '#'});

        if (typeof button != 'undefined') {
            this.button = $(button);
            var handler;

            this.options.merge(this.button.readAttribute('data-options'));
            if (this.options.isProduct) {
                handler = this.productViewHandler.bindAsEventListener(this);
            } else {
                handler = this.cartHandler.bindAsEventListener(this);
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

    cartHandler: function () {
        setLocation(this.options.redirectUrl);
    },

    /**
     *
     * @param event
     */
    productViewHandler: function (event) {
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

    $$('.vipps-express-checkout .vipps-checkout').each(function (button) {
        new VippsExpressCheckout(button);
    });
});
