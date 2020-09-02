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

/**
 * Class OrderManagement
 */
class Vipps_Payment_Model_OrderPlace
{
    use Vipps_Payment_Model_Helper_Formatter;

    /**
     * @var \Vipps_Payment_Model_OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Vipps_Payment_Model_Adapter_CartRepository
     */
    private $cartRepository;

    /**
     * @var \Vipps_Payment_Model_Adapter_CartManagement
     */
    private $cartManagement;

    /**
     * @var Vipps_Payment_Model_QuoteLocator
     */
    private $quoteLocator;

    /**
     * @var \Vipps_Payment_Model_QuoteUpdater
     */
    private $quoteUpdater;

    /**
     * @var \Vipps_Payment_Model_Helper_LockManager
     */
    private $lockManager;

    /**
     * @var Vipps_Payment_Gateway_Config_Config
     */
    private $config;

    /**
     * @var \Vipps_Payment_Model_QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Vipps_Payment_Model_Adapter_Logger
     */
    private $logger;

    /**
     * OrderPlace constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct()
    {
        $this->orderRepository = Mage::getSingleton('vipps_payment/orderRepository');
        $this->cartRepository = Mage::getSingleton('vipps_payment/adapter_cartRepository');
        $this->cartManagement = Mage::getSingleton('vipps_payment/adapter_cartManagement');
        $this->quoteUpdater = Mage::getSingleton('vipps_payment/quoteUpdater');
        $this->lockManager = Mage::getSingleton('vipps_payment/helper_lockManager');
        $this->config = Mage::helper('vipps_payment/gateway')->getSingleton('config_config');
        $this->quoteManagement = Mage::getSingleton('vipps_payment/quoteManagement');
        $this->logger = Mage::getSingleton('vipps_payment/adapter_logger');
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return Mage_Sales_Model_Order|null
     * @throws Vipps_Payment_Gateway_Exception_VippsException
     * @throws Vipps_Payment_Gateway_Exception_WrongAmountException
     * @throws Mage_Core_Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(Mage_Sales_Model_Quote $quote, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
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
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return bool
     */
    private function canPlaceOrder(Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        return $transaction->isTransactionReserved();
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return bool|string
     * @throws Mage_Core_Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function acquireLock(Mage_Sales_Model_Quote $quote)
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
     * @param Mage_Sales_Model_Quote $quote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return Mage_Sales_Model_Order
     * @throws Vipps_Payment_Gateway_Exception_VippsException
     * @throws Vipps_Payment_Gateway_Exception_WrongAmountException
     * @throws Mage_Core_Exception
     */
    private function placeOrder(Mage_Sales_Model_Quote $quote, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        $clonedQuote = clone $quote;
        $reservedOrderId = $clonedQuote->getReservedOrderId();
        if (!$reservedOrderId) {
            return null;
        }

        $order = $this->orderRepository->getByIncrement($reservedOrderId);
        if (!$order) {
            //this is used only for express checkout
            $this->quoteUpdater->execute($clonedQuote, $transaction);
            /** @var Mage_Sales_Model_Quote $clonedQuote */
            $clonedQuote = $this->cartRepository->get($clonedQuote->getId());
            if ($clonedQuote->getReservedOrderId() !== $reservedOrderId) {
                return null;
            }

            $this->validateAmount($clonedQuote, $transaction);

            // set quote active, collect totals and place order
            $clonedQuote->setIsActive(true);
            $orderId = $this->cartManagement->placeOrder($clonedQuote->getId());
            $order = $this->orderRepository->getByIncrement($orderId);
            $clonedQuote->setIsActive(false);
            $quote->setIsActive(false);
        }

        $clonedQuote->setReservedOrderId(null);
        $this->cartRepository->save($clonedQuote);

        return $order;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
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
     * @param Mage_Sales_Model_Quote $quote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return void
     * @throws Vipps_Payment_Gateway_Exception_WrongAmountException
     */
    private function validateAmount(Mage_Sales_Model_Quote $quote, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        $grandTotal = $quote->getGrandTotal();
        $quoteAmount = (int)round($this->formatPrice($grandTotal) * 100);
        $vippsAmount = (int)$transaction->getTransactionSummary()->getRemainingAmountToCapture();

        if ($quoteAmount != $vippsAmount) {
            throw new Vipps_Payment_Gateway_Exception_WrongAmountException(
                __("Quote Grand Total {$quoteAmount} does not match Transaction Amount {$vippsAmount}")
            );
        }
    }

    /**
     * Update vipps quote with success.
     *
     * @param Mage_Sales_Model_Quote $cart
     */
    private function updateVippsQuote(Mage_Sales_Model_Quote $cart)
    {
        try {
            $vippsQuote = $this->quoteManagement->getByQuote($cart);
            $vippsQuote->setStatus(Vipps_Payment_Model_QuoteStatusInterface::STATUS_PLACED);
            $this->quoteManagement->save($vippsQuote);
        } catch (\Exception $e) {
            // Order is submitted but failed to update Vipps Quote. It should not affect order flow.
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Capture
     *
     * @param Mage_Sales_Model_Order $order
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     * @throws Mage_Core_Exception
     */
    private function capture(Mage_Sales_Model_Order $order, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        if ($order->getState() !== Mage_Sales_Model_Order::STATE_NEW) {
            return;
        }

        // preconditions
        $totalDue = $order->getTotalDue();
        $baseTotalDue = $order->getBaseTotalDue();

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $payment->setAmountAuthorized($totalDue);
        $payment->setBaseAmountAuthorized($baseTotalDue);

        $transactionId = $transaction->getTransactionId();
        $payment->setTransactionId($transactionId);
        $payment->setTransactionAdditionalInfo(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
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
     * @param Mage_Sales_Model_Order $order
     */
    private function notify(Mage_Sales_Model_Order $order)
    {
        if ($order->getCanSendNewEmailFlag() && !$order->getEmailSent()) {
            $order->sendOrderUpdateEmail(true);
        }
    }

    /**
     * Authorize action
     *
     * @param Mage_Sales_Model_Order $order
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     * @throws Exception
     */
    private function authorize(Mage_Sales_Model_Order $order, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        if ($order->getState() !== Mage_Sales_Model_Order::STATE_NEW) {
            return;
        }

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        $transactionId = $transaction->getTransactionId();
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(false);
        $payment->setTransactionAdditionalInfo(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
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
     * @throws Mage_Core_Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function releaseLock($lockName)
    {
        return $this->lockManager->unlock($lockName);
    }
}
