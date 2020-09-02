$(document).observe('dom:loaded', function () {
    Review.prototype.save = Review.prototype.save.wrap(
        function (callOriginal) {
            if (payment.currentMethod === 'vipps') {
                checkout.setLoadWaiting('review');
                jQuery.post(
                    '/vipps/payment_regular',
                    {}
                ).done(
                    function (response, msg, xhr) {
                        checkout.setLoadWaiting(false);
                        return callOriginal();
                    }
                );
            } else {
                return callOriginal();
            }
        }
    );
});
