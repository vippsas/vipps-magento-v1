<?php
/**
 * Copyright 2020 Vipps
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 *    documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 *  and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

/**
 * Class Vipps_Payment_Model_Observer_CheckoutSubmitAllAfter
 */
class Vipps_Payment_Model_Observer_CheckoutSubmitAllAfter
{
    /** @var string  */
    const VIPPS_URL_KEY = 'vipps_url';

    /**
     * @var Vipps_Payment_Model_QuoteManagement
     */
    private $vippsQuoteManagement;

    /**
     * @var Vipps_Payment_Model_Adapter_Logger
     */
    private $logger;

    /**
     * Vipps_Payment_Model_Observer_CheckoutSubmitAllAfter constructor.
     */
    public function __construct()
    {
        $this->vippsQuoteManagement =  Mage::getSingleton('vipps_payment/quoteManagement');
        $this->logger = Mage::getSingleton('vipps_payment/adapter_logger');
    }

    public function setRedirectUrl(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order =  $observer->getEvent()->getData('order');
        $quote = $observer->getEvent()->getData('quote');
        if (!$order || !$quote) {
            return;
        }

        $payment = $order->getPayment();
        if (!$payment || $payment->getMethod() != 'vipps') {
            return;
        }

        try {
            // updated vipps quote
            $vippsQuote = $this->vippsQuoteManagement->getByQuote($quote);
            $vippsQuote->setOrderId((int)$order->getEntityId());
            $vippsQuote->setStatus(Vipps_Payment_Model_QuoteStatusInterface::STATUS_NEW);
            $this->vippsQuoteManagement->save($vippsQuote);
        } catch (\Throwable $t) {
            $this->logger->error($t);
        }

        $redirectUrl = $quote->getPayment()->getAdditionalInformation(
            self::VIPPS_URL_KEY
        );

        if ($redirectUrl) {
            Mage::getSingleton('checkout/session')->setRedirectUrl($redirectUrl);
            $quote->getPayment()->setAdditionalInformation(
                self::VIPPS_URL_KEY
            );
            $quote->save();
        }
    }
}
