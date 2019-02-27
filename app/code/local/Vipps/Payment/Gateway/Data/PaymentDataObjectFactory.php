<?php
/**
 * Copyright 2019 Vipps
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
 * Class PaymentDataObjectFactory
 */
class Vipps_Payment_Gateway_Data_PaymentDataObjectFactory
{
    /**
     * Create payment data Object.
     *
     * @param \Mage_Payment_Model_Info $paymentInfo
     * @return Vipps_Payment_Gateway_Data_PaymentDataObject
     */
    public function create(\Mage_Payment_Model_Info $paymentInfo)
    {
        $order = null;
        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            /** @var $paymentInfo Mage_Sales_Model_Order_Payment */
            $order = new Vipps_Payment_Gateway_Data_OrderAdapter($paymentInfo->getOrder());

        } elseif ($paymentInfo instanceof Mage_Sales_Model_Quote_Payment) {
            $order = new Vipps_Payment_Gateway_Data_QuoteAdapter($paymentInfo->getQuote());
        }

        return new Vipps_Payment_Gateway_Data_PaymentDataObject($order, $paymentInfo);
    }
}
