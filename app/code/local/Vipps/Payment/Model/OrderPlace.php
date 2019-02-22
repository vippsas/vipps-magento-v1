<?php
/**
 * Copyright 2018 Vipps
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

namespace Vipps\Payment\Model;

use Vipps\Payment\Gateway\Config\Config;
use Vipps\Payment\Gateway\Exception\VippsException;
use Vipps\Payment\Gateway\Exception\WrongAmountException;
use Vipps\Payment\Gateway\Transaction\Transaction;
use Vipps\Payment\Model\Adapter\CartRepository;
use Vipps\Payment\Model\Helper\Formatter;
use Vipps\Payment\Model\Helper\LockManager;

/**
 * Class OrderManagement
 * @package Vipps\Payment\Model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderPlace
{
    use Formatter;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var CartRepository
     */
    private $cartRepository;

    /**
     * @var \Mage_Checkout_Model_Cart_Api
     */
    private $cartManagement;

    /**
     * @var QuoteLocator
     */
    private $quoteLocator;

    /**
     * @var QuoteUpdater
     */
    private $quoteUpdater;

    /**
     * @var LockManager
     */
    private $lockManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Vipps\Payment\Model\Adapter\Logger
     */
    private $logger;

    /**
     * OrderPlace constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct()
    {
        $this->orderRepository = new OrderRepository();
        $this->cartRepository = new CartRepository();
        $this->cartManagement = \Mage::getModel('checkout/cart_api');
        $this->quoteUpdater = new QuoteUpdater();
        $this->lockManager = new LockManager();
        $this->config = new Config();
        $this->quoteManagement = new QuoteManagement();
        $this->logger = new Adapter\Logger();
    }

    /**
     * @param \Mage_Sales_Model_Quote $quote
     * @param Transaction $transaction
     *
     * @return \Mage_Sales_Model_Order|null
     * @throws VippsException
     * @throws WrongAmountException
     * @throws \Mage_Core_Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(\Mage_Sales_Model_Quote $quote, Transaction $transaction)
    {
        if (!$this->canPlaceOrder($transaction)) {
            return null;
        }

        $lockName = $this->acquireLock($quote);
        if (!$lockName) {
            return null;
        }

        try {
            $order = $this->placeOrder($quote, $transaction);

            if ($order) {
                $this->updateVippsQuote($quote);
                $paymentAction = $this->config->getValue('vipps_payment_action');
                switch ($paymentAction) {
                    case \Vipps_Payment_Model_System_Config_Source_PaymentAction::ACTION_AUTHORIZE_CAPTURE:
                        $this->capture($order, $transaction);
                        break;
                    default:
                        $this->authorize($order, $transaction);
                }
            }

            return $order;
        } finally {
            $this->releaseLock($lockName);
        }
    }

    /**
     * Check can we place order or not based on transaction object
     *
     * @param Transaction $transaction
     *
     * @return bool
     */
    private function canPlaceOrder(Transaction $transaction)
    {
        return $transaction->isTransactionReserved();
    }

    /**
     * @param \Mage_Sales_Model_Quote $quote
     *
     * @return bool|string
     * @throws \Mage_Core_Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function acquireLock(\Mage_Sales_Model_Quote $quote)
    {
        $reservedOrderId = $quote->getReservedOrderId();
        if ($reservedOrderId) {
            $lockName = 'vipps_place_order_' . $reservedOrderId;
            if ($this->lockManager->lock($lockName, 10)) {
                return $lockName;
            }
        }
        return false;
    }

    /**
     * @param \Mage_Sales_Model_Quote $quote
     * @param Transaction $transaction
     *
     * @return \Mage_Sales_Model_Order
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws VippsException
     * @throws WrongAmountException
     */
    private function placeOrder(\Mage_Sales_Model_Quote $quote, Transaction $transaction)
    {
        $clonedQuote = clone $quote;
        $reservedOrderId = $clonedQuote->getReservedOrderId();
        if (!$reservedOrderId) {
            return null;
        }

        $order = $this->orderRepository->getByIncrement($reservedOrderId);
        if (!$order) {
            //this is used only for express checkout
            $this->quoteUpdater->execute($clonedQuote);
            /** @var \Mage_Sales_Model_Quote $clonedQuote */
            $clonedQuote = $this->cartRepository->get($clonedQuote->getId());
            if ($clonedQuote->getReservedOrderId() !== $reservedOrderId) {
                return null;
            }

            $this->prepareQuote($clonedQuote);

            $clonedQuote->getShippingAddress()->setCollectShippingRates(true);
            $clonedQuote->collectTotals();

            $this->validateAmount($clonedQuote, $transaction);

            // set quote active, collect totals and place order
            $clonedQuote->setIsActive(true);
            $orderId = $this->cartManagement->createOrder($clonedQuote->getId());
            $order = $this->orderRepository->getByIncrement($orderId);
        }

        $clonedQuote->setReservedOrderId(null);
        $this->cartRepository->save($clonedQuote);

        return $order;
    }

    /**
     * @param \Mage_Sales_Model_Quote $quote
     */
    private function prepareQuote($quote)
    {
        $websiteId = $quote->getStore()->getWebsiteId();
        foreach ($quote->getAllItems() as $item) {
            /** @var Quote\Item $item */
            $item->getProduct()->setWebsiteId($websiteId);
        }
    }

    /**
     * Check if reserved Order amount in vipps is the same as in Magento.
     *
     * @param \Mage_Sales_Model_Quote $quote
     * @param Transaction $transaction
     *
     * @return void
     * @throws WrongAmountException
     */
    private function validateAmount(\Mage_Sales_Model_Quote $quote, Transaction $transaction)
    {
        $quoteAmount = (int)($this->formatPrice($quote->getGrandTotal()) * 100);
        $vippsAmount = (int)$transaction->getTransactionInfo()->getAmount();

        if ($quoteAmount != $vippsAmount) {
            throw new WrongAmountException(
                __("Quote Grand Total {$quoteAmount} does not match Transaction Amount {$vippsAmount}")
            );
        }
    }

    /**
     * Update vipps quote with success.
     *
     * @param \Mage_Sales_Model_Quote $cart
     */
    private function updateVippsQuote(\Mage_Sales_Model_Quote $cart)
    {
        try {
            $vippsQuote = $this->quoteManagement->getByQuote($cart);
            $vippsQuote->setStatus(QuoteStatusInterface::STATUS_PLACED);
            $this->quoteManagement->save($vippsQuote);
        } catch (\Throwable $e) {
            // Order is submitted but failed to update Vipps Quote. It should not affect order flow.
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Capture
     *
     * @param \Mage_Sales_Model_Order $order
     * @param Transaction $transaction
     * @throws \Mage_Core_Exception
     */
    private function capture(\Mage_Sales_Model_Order $order, Transaction $transaction)
    {
        if ($order->getState() !== \Mage_Sales_Model_Order::STATE_NEW) {
            return;
        }

        // preconditions
        $totalDue = $order->getTotalDue();
        $baseTotalDue = $order->getBaseTotalDue();

        /** @var \Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $payment->setAmountAuthorized($totalDue);
        $payment->setBaseAmountAuthorized($baseTotalDue);

        $transactionId = $transaction->getTransactionId();
        $payment->setTransactionId($transactionId);
        $payment->setTransactionAdditionalInfo(
            \Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $transaction->getTransactionInfo()->getData()
        );

        // do capture
        $payment->capture(null);
        $this->orderRepository->save($order);

        $this->notify($order);
    }

    /**
     * Send order conformation email if not sent
     *
     * @param \Mage_Sales_Model_Order $order
     */
    private function notify(\Mage_Sales_Model_Order $order)
    {
        if ($order->getCanSendNewEmailFlag() && !$order->getEmailSent()) {
            $order->sendOrderUpdateEmail(true);
        }
    }

    /**
     * Authorize action
     *
     * @param \Mage_Sales_Model_Order $order
     * @param Transaction $transaction
     */
    private function authorize(\Mage_Sales_Model_Order $order, Transaction $transaction)
    {
        if ($order->getState() !== \Mage_Sales_Model_Order::STATE_NEW) {
            return;
        }

        /** @var \Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $transactionId = $transaction->getTransactionId();
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(false);
        $payment->setTransactionAdditionalInfo(
            \Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $transaction->getTransactionInfo()->getData()
        );

        // preconditions
        $totalDue = $order->getTotalDue();
        $baseTotalDue = $order->getBaseTotalDue();

        // do authorize
        $payment->authorize(false, $baseTotalDue);
        // base amount will be set inside
        $payment->setAmountAuthorized($totalDue);
        $this->orderRepository->save($order);

        $this->notify($order);
    }

    /**
     * @param $lockName
     *
     * @return bool
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function releaseLock($lockName)
    {
        return $this->lockManager->unlock($lockName);
    }
}
