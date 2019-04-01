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
 * Class AbstractDataBuilder
 * @package Vipps\Payment\Gateway\Request\Initiate
 */
abstract class Vipps_Payment_Gateway_Request_Initiate_AbstractDataBuilder implements Vipps_Payment_Gateway_Request_Initiate_InitiateBuilderInterface
{
    /**
     * @var \Vipps_Payment_Gateway_Request_SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Mage_Core_Model_Url
     */
    protected $urlBuilder;

    /**
     * @var \Vipps_Payment_Model_Adapter_CartRepository
     */
    protected $cartRepository;

    public function __construct()
    {
        $this->subjectReader = Mage::helper('vipps_payment/gateway')->getSingleton('request_subjectReader');
        $this->urlBuilder = Mage::getSingleton('core/url');
        $this->cartRepository = Mage::getSingleton('vipps_payment/adapter_cartRepository');
    }
}
