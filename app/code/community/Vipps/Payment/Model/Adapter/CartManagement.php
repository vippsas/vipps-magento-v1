<?php
/**
 * Copyright 2021 Vipps
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

/**
 * Class CartManagement
 */
class Vipps_Payment_Model_Adapter_CartManagement
{
    /**
     * @var Vipps_Payment_Model_Cart_Api
     */
    private $orderApi;

    /**
     * @var Vipps_Payment_Model_Adapter_Logger
     */
    private $logger;

    /**
     * CartManagement constructor.
     */
    public function __construct()
    {
        $this->orderApi = Mage::getModel('vipps_payment/cart_api');
        $this->logger = Mage::getSingleton('vipps_payment/adapter_logger');
    }

    /**
     * @param $cartId
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function placeOrder($cartId)
    {
        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = $this->orderApi->createOrder($cartId, null, Mage::helper('checkout')->getRequiredAgreementIds());

            if ($order !== null) {
                return $order;
            }
        } catch (Mage_Api_Exception $e){
            $this->logger->critical($e->getMessage());
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        throw new Mage_Core_Exception(
            __('An error occurred on the server. Please try to place the order again.')
        );
    }
}

