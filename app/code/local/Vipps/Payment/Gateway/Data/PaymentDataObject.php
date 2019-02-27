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

class Vipps_Payment_Gateway_Data_PaymentDataObject implements Vipps_Payment_Gateway_Data_PaymentDataObjectInterface
{
    /**
     * @var \Mage_Core_Model_Abstract
     */
    private $order;

    /**
     * @var \Mage_Payment_Model_Info
     */
    private $payment;

    /**
     * @param \Mage_Core_Model_Abstract $order
     * @param \Mage_Payment_Model_Info $payment
     */
    public function __construct(
        Vipps_Payment_Gateway_Data_OrderAdapterInterface $order = null,
        \Mage_Payment_Model_Info $payment = null
    ) {
        $this->order = $order;
        $this->payment = $payment;
    }

    /**
     * Returns order
     *
     * @return Vipps_Payment_Gateway_Data_OrderAdapterInterface
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Returns payment
     *
     * @return \Mage_Payment_Model_Info
     */
    public function getPayment()
    {
        return $this->payment;
    }
}
