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

use Vipps\Payment\Gateway\Command\CommandManager;
use Vipps\Payment\Gateway\Config\Config;
use Vipps\Payment\Gateway\Transaction\TransactionBuilder;
use Vipps\Payment\Model\Adapter\JsonEncoder;
use Vipps\Payment\Model\Adapter\MessageManager;
use Vipps\Payment\Model\Adapter\Logger;
use Vipps\Payment\Model\Gdpr\Compliance;

class Vipps_Payment_Controller_Abstract extends Mage_Core_Controller_Front_Action
{
    const STATUS_CODE_200 = 200;
    const STATUS_CODE_500 = 500;

    /**
     * @var CommandManager
     */
    protected $commandManager;

    /**
     * @var Mage_Checkout_Model_Session
     */
    protected $cart;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var MessageManager
     */
    protected $messageManager;

    /**
     * @var JsonEncoder
     */
    protected $serializer;

    /**
     * @var Compliance
     */
    protected $gdprCompliance;

    /**
     * @var TransactionBuilder
     */
    protected $transactionBuilder;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->cart = Mage::getSingleton('checkout/cart');
        $this->logger = new Logger();
        $this->commandManager = new CommandManager();
        $this->config = new Config();
        $this->messageManager = new MessageManager();
        $this->serializer = new JsonEncoder();
        $this->gdprCompliance = new Compliance();
        $this->transactionBuilder = new TransactionBuilder;

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
