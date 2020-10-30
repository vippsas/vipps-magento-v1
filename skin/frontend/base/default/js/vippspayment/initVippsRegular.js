$(document).observe('dom:loaded', function () {
    Review.prototype.save = Review.prototype.save.wrap(
        function (callOriginal) {
            if (payment.currentMethod === 'vipps') {
                checkout.setLoadWaiting('review');
                jQuery.post(
                    BASE_URL +'vipps/payment_regular',
                    {}
                ).done(
                    function (response, msg, xhr) {
                        checkout.setLoadWaiting(false);
                        if (typeof response.url !== 'undefined') {
                            return callOriginal();
                        } else {
                            window.location.href = BASE_URL + 'checkout/cart';
                        }
                    }
                );
            } else {
                return callOriginal();
            }
        }
    );
});
