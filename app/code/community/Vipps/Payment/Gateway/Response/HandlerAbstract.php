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

abstract class Vipps_Payment_Gateway_Response_HandlerAbstract implements Vipps_Payment_Gateway_Response_HandlerInterface
{
    /**
     * @var \Vipps_Payment_Gateway_Request_SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Vipps_Payment_Gateway_Transaction_TransactionBuilder
     */
    protected $transactionBuilder;

    public function __construct()
    {
        $this->subjectReader = Mage::helper('vipps_payment/gateway')->getSingleton('request_subjectReader');
        $this->transactionBuilder = new Vipps_Payment_Gateway_Transaction_TransactionBuilder();
    }
}
