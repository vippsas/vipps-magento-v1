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

//use Magento\Framework\Controller\ResultFactory;
//use Magento\Framework\Controller\ResultInterface;
//use Magento\Framework\App\Action\Context;
//use Magento\Framework\App\Action\Action;
//use Magento\Framework\App\ResponseInterface;
//use Magento\Framework\Session\SessionManagerInterface;
//use Magento\Payment\Gateway\ConfigInterface;
//use Vipps\Payment\Api\CommandManagerInterface;

use Vipps\Payment\Gateway\Command\CommandManager;
use Vipps\Payment\Gateway\Config\Config;
use Vipps\Payment\Gateway\Exception\VippsException;
use Vipps\Payment\Gateway\Request\Initiate\InitiateBuilderInterface;
use Vipps\Payment\Model\Adapter\MessageManager;

class Vipps_Payment_ExpressController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var CommandManager
     */
    private $commandManager;

    /**
     * @var Mage_Checkout_Model_Session
     */
    private $session;

    /**
     * @var Vipps_Payment_Model_Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var MessageManager
     */
    private $messageManager;

    public function preDispatch()
    {
        $this->session = Mage::getSingleton('checkout/session');
        $this->logger = Mage::getSingleton('vipps_payment/logger');
        $this->commandManager = new CommandManager();
        $this->config = new Config();
        $this->messageManager = new MessageManager();

        parent::preDispatch();

        return $this;
    }

    public function indexAction()
    {

        $redirectPath = 'checkout/cart';
        try {
            if (!$this->config->getValue('express_checkout')) {
                throw new Mage_Core_Exception(__('Express Payment method is not available.'));
            }
            $quote = $this->session->getQuote();
            $responseData = $this->commandManager->initiatePayment(
                $quote->getPayment(),
                [
                    'amount'                                   => $quote->getGrandTotal(),
                    InitiateBuilderInterface::PAYMENT_TYPE_KEY => InitiateBuilderInterface::PAYMENT_TYPE_EXPRESS_CHECKOUT
                ]
            );
            $this->session->unsetAll();
            $redirectPath = $responseData['url'];
        } catch (VippsException $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred during request to Vipps. Please try again later.')
            );
        }

        return $this->_redirect($redirectPath, ['_secure' => true]);

    }
}
