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

class Vipps_Payment_Gateway_Request_BuilderComposite_VippsInitiateRequest extends Vipps_Payment_Gateway_Request_BuilderComposite
{
    public function __construct()
    {
        /**
         * @var $helper Vipps_Payment_Helper_Gateway
         */
        $helper = Mage::helper('vipps_payment/gateway');
        $builders = [
            'customerInfo'         => $helper->getSingleton('request_initiate_customerDataBuilder'),
            'merchantInfo'         => $helper->getSingleton('request_initiate_merchantDataBuilder'),
            'merchantSerialNumber' => $helper->getSingleton('request_merchantDataBuilder'),
            'transaction'          => $helper->getSingleton('request_initiate_transactionDataBuilder'),
            'transactionText'      => $helper->getSingleton('request_transactionTextDataBuilder'),
        ];

        parent::__construct($builders);
    }
}
