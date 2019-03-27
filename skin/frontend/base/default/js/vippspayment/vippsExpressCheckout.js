var VippsExpressCheckout = Class.create();
/**
 * Add Vipps express checkout parameter when adds product to cart.
 * @type {{buttonObserver: VippsExpressCheckout.buttonObserver, getAddToCartForm: VippsExpressCheckout.getAddToCartForm, initialize: VippsExpressCheckout.initialize}}
 */
VippsExpressCheckout.prototype = {

    /**
     * @param button
     */
    initialize: function (button) {
        this.options = $H({redirectUrl: '#'});

        if (typeof button != 'undefined') {
            this.button = $(button);
            this.options.merge(this.button.readAttribute('data-options'));
            this.button.observe('click', this.productViewHandler.bindAsEventListener(this));
        }
    },

    /**
     * Resolve Addtocart form
     * @returns {*}
     */
    getAddToCartForm: function () {
        // Add an ability to pass
        if (this.options.get('cartForm') instanceof VarienForm) {
            return this.options.get('cartForm')
        }

        if (('undefined' != typeof productAddToCartFormOld) && productAddToCartFormOld) {
            return productAddToCartFormOld
        }
        if (('undefined' != typeof productAddToCartForm) && productAddToCartForm) {
            return productAddToCartForm
        }

    },

    /**
     * Handler product view button.
     * @param event
     */
    productViewHandler: function (event) {
        Event.stop(event);

        var productAddToCartForm = this.getAddToCartForm();
        if (!productAddToCartForm) {
            console.error('productAddToCartForm is not defined');
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
