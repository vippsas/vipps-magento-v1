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

class Vipps_Payment_Controller_Abstract extends Mage_Core_Controller_Front_Action
{
    const STATUS_CODE_200 = 200;
    const STATUS_CODE_500 = 500;

    /**
     * @var Vipps_Payment_Gateway_Command_CommandManager
     */
    protected $commandManager;

    /**
     * @var Mage_Checkout_Model_Session
     */
    protected $cart;

    /**
     * @var Vipps_Payment_Model_Adapter_Logger
     */
    protected $logger;

    /**
     * @var Vipps_Payment_Gateway_Config_Config
     */
    protected $config;

    /**
     * @var Vipps_Payment_Model_Adapter_MessageManager
     */
    protected $messageManager;

    /**
     * @var Vipps_Payment_Model_Adapter_JsonEncoder
     */
    protected $serializer;

    /**
     * @var Vipps_Payment_Model_Gdpr_Compliance
     */
    protected $gdprCompliance;

    /**
     * @var Vipps_Payment_Gateway_Transaction_TransactionBuilder
     */
    protected $transactionBuilder;

    /**
     * @var Vipps_Payment_Helper_Gateway
     */
    protected $helper;

    /**
     * @return $this|Mage_Core_Controller_Front_Action
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->helper = Mage::helper('vipps_payment/gateway');
        $this->cart = Mage::getSingleton('checkout/cart');
        $this->logger = Mage::getSingleton('vipps_payment/adapter_logger');
        $this->commandManager = $this->helper->getSingleton('command_commandManager');
        $this->config = $this->helper->getSingleton('config_config');
        $this->messageManager = Mage::getSingleton('vipps_payment/adapter_messageManager');
        $this->serializer = Mage::getSingleton('vipps_payment/adapter_jsonEncoder');
        $this->gdprCompliance = Mage::getSingleton('vipps_payment/gdpr_compliance');
        $this->transactionBuilder = new Vipps_Payment_Gateway_Transaction_TransactionBuilder;

        return $this;
    }

    /**
     * Echo json as response.
     *
     * @param $data
     */
    protected function _renderJson($data)
    {
        $this->getResponse()
            ->setHeader('Content-type', 'application/json', true)
            ->setBody($this->serializer->serialize($data));
    }
}
