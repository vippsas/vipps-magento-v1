<?php
/**
 * Copyright 2020 Vipps
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
class Vipps_Payment_Model_TransactionProcessor
{
    use Vipps_Payment_Model_Helper_Formatter;

    /**
     * @var \Vipps_Payment_Model_Helper_LockManager
     */
    private $lockManager;

    /**
     * @var \Vipps_Payment_Model_QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Vipps_Payment_Model_OrderRepository
     */
    private $orderRepository;

    /**
     * @var Vipps_Payment_Model_QuoteLocator
     */
    private $quoteLocator;

    /**
     * @var \Vipps_Payment_Model_QuoteUpdater
     */
    private $quoteUpdater;

    /**
     * @var Vipps_Payment_Gateway_Config_Config
     */
    private $config;

    /**
     * @var \Vipps_Payment_Model_Adapter_CartRepository
     */
    private $cartRepository;

    /**
     * @var \Vipps_Payment_Model_Adapter_CartManagement
     */
    private $cartManagement;

    /**
     * @var Vipps_Payment_Gateway_Command_PaymentDetailsProvider
     */
    private $paymentDetailsProvider;

    /**
     * Vipps_Payment_Model_TransactionProcessor constructor.
     */
    public function __construct()
    {
        $this->lockManager = Mage::getSingleton('vipps_payment/helper_lockManager');
        $this->quoteManagement = Mage::getSingleton('vipps_payment/quoteManagement');
        $this->orderRepository = Mage::getSingleton('vipps_payment/orderRepository');
        $this->quoteLocator = Mage::getSingleton('vipps_payment/quoteLocator');
        $this->quoteUpdater = Mage::getSingleton('vipps_payment/quoteUpdater');
        $this->config = Mage::helper('vipps_payment/gateway')->getSingleton('config_config');
        $this->cartRepository = Mage::getSingleton('vipps_payment/adapter_cartRepository');
        $this->cartManagement = Mage::getSingleton('vipps_payment/adapter_cartManagement');
        $this->paymentDetailsProvider = Mage::helper('vipps_payment/gateway')->getSingleton('command_paymentDetailsProvider');
    }

    /**
     * @param Vipps_Payment_Model_Quote $vippsQuote
     *
     * @throws Mage_Core_Exception
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function process(
        Vipps_Payment_Model_Quote $vippsQuote
    ) {
        try {
            $lockName = $this->acquireLock($vippsQuote->getReservedOrderId());

            /** @var Vipps_Payment_Gateway_Transaction_Transaction $transaction */
            $transaction = $this->paymentDetailsProvider->get(
                ['orderId' =>$vippsQuote->getReservedOrderId()]
            );

            if ($transaction->transactionWasCancelled() || $transaction->transactionWasVoided()) {
                $this->processCancelledTransaction($vippsQuote);
            } elseif ($transaction->isTransactionReserved()) {
                $this->processReservedTransaction($vippsQuote, $transaction);
            } elseif ($transaction->isTransactionExpired()) {
                $this->processExpiredTransaction($vippsQuote);
            }

            return $transaction;
        } finally {
            $this->releaseLock($lockName);
        }
    }

    /**
     * @param Vipps_Payment_Model_Quote $vippsQuote
     *
     * @throws Mage_Core_Exception
     */
    private function processCancelledTransaction(
        Vipps_Payment_Model_Quote $vippsQuote
    ) {
        if ($vippsQuote->getOrderId()) {
            $order = $this->orderRepository->getById($vippsQuote->getOrderId());
            $order->cancel();
            $order->save();
        }

        $vippsQuote->setStatus(Vipps_Payment_Model_Quote::STATUS_CANCELED);
        $this->quoteManagement->save($vippsQuote);
    }

    /**
     * @param Vipps_Payment_Model_Quote $vippsQuote
     *
     * @throws Mage_Core_Exception
     */
    private function processExpiredTransaction(
        Vipps_Payment_Model_Quote $vippsQuote
    ) {
        if ($vippsQuote->getOrderId()) {
            $order = $this->orderRepository->getById($vippsQuote->getOrderId());
            $order->cancel();
            $order->save();
        }

        $vippsQuote->setStatus(Vipps_Payment_Model_Quote::STATUS_EXPIRED);
        $this->quoteManagement->save($vippsQuote);
    }

    /**
     * @param Vipps_Payment_Model_Quote $vippsQuote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return Mage_Core_Model_Abstract|Mage_Sales_Model_Order|null
     * @throws Mage_Core_Exception
     */
    private function processReservedTransaction(
        Vipps_Payment_Model_Quote $vippsQuote,
        Vipps_Payment_Gateway_Transaction_Transaction $transaction
    ) {
        if ($vippsQuote->getOrderId()) {
            $order = $this->orderRepository->getById($vippsQuote->getOrderId());
        } else {
            $order = $this->placeOrder($transaction);
            $vippsQuote->setOrderId($order->getId());
        }

        $paymentAction = $this->config->getValue('vipps_payment_action');
        $this->processAction($paymentAction, $order, $transaction);

        $this->notify($order);

        $vippsQuote->setStatus(Vipps_Payment_Model_Quote::STATUS_PLACED);
        $this->quoteManagement->save($vippsQuote);

        return $order;
    }

    /**
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return Mage_Core_Model_Abstract|Mage_Sales_Model_Order|null
     * @throws Mage_Core_Exception
     * @throws Vipps_Payment_Gateway_Exception_WrongAmountException
     */
    private function placeOrder(
        Vipps_Payment_Gateway_Transaction_Transaction $transaction
    ) {
        $quote = $this->quoteLocator->get($transaction->getOrderId());
        if (!$quote) {
            throw new \Exception( //@codingStandardsIgnoreLine
                __('Could not place order. Could not find quote with such reserved order id.')
            );
        }
        if (!$quote->getReservedOrderId() || $quote->getReservedOrderId() !== $transaction->getOrderId()) {
            throw new \Exception( //@codingStandardsIgnoreLine
                __('Quote reserved order id does not match Vipps transaction order id.')
            );
        }
        $reservedOrderId = $quote->getReservedOrderId();
        $clonedQuote =  clone $quote;

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
     * @param $paymentAction
     * @param Mage_Sales_Model_Order $order
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @throws Mage_Core_Exception
     */
    private function processAction(
        $paymentAction,
        Mage_Sales_Model_Order $order,
        Vipps_Payment_Gateway_Transaction_Transaction $transaction
    ) {
        switch ($paymentAction) {
            case \Vipps_Payment_Model_System_Config_Source_PaymentAction::ACTION_AUTHORIZE_CAPTURE:
                $this->capture($order, $transaction);
                break;
            default:
                $this->authorize($order, $transaction);
        }
    }

    /**
     * Capture
     *
     * @param Mage_Sales_Model_Order $order
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     * @throws Mage_Core_Exception
     */
    private function capture(
        Mage_Sales_Model_Order $order,
        Vipps_Payment_Gateway_Transaction_Transaction $transaction
    ) {
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
    }

    /**
     * Authorize action
     *
     * @param Mage_Sales_Model_Order $order
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     * @throws Exception
     */
    private function authorize(
        Mage_Sales_Model_Order $order,
        Vipps_Payment_Gateway_Transaction_Transaction $transaction
    ) {
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
    }


    /**
     * Send order conformation email if not sent
     *
     * @param Mage_Sales_Model_Order $order
     */
    private function notify(Mage_Sales_Model_Order $order)
    {
        $payment = $order->getPayment();
        if (
            $order->getCanSendNewEmailFlag() &&
            !$order->getEmailSent() &&
            !$payment->getAdditionalInformation('email_added_to_queue')
        ) {
            $order->sendOrderUpdateEmail(true);
            $payment->setAdditionalInformation('email_added_to_queue', true);
            $payment->save();
        }
    }

    /**
     * @param $reservedOrderId
     *
     * @return bool|string
     * @throws Mage_Core_Exception
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    private function acquireLock($reservedOrderId)
    {
        $lockName = 'vipps_place_order_' . $reservedOrderId;
        $retries = 0;
        $canLock = $this->lockManager->lock($lockName, 10);

        while (!$canLock && ($retries < 10)) {
            usleep(200000); //wait for 0.2 seconds
            $canLock = $this->lockManager->lock($lockName, 10);
            if (!$canLock) {
                $retries++;
            }
        }

        if (!$canLock) {
            throw new Vipps_Payment_Model_Exception_AcquireLock(
                __('Can not acquire lock for order \"%1\"', $reservedOrderId)
            );
        }

        return $lockName;
    }

    /**
     * @param $lockName
     *
     * @return bool
     * @throws Mage_Core_Exception
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    private function releaseLock($lockName)
    {
        return $this->lockManager->unlock($lockName);
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
    private function validateAmount(
        Mage_Sales_Model_Quote $quote,
        Vipps_Payment_Gateway_Transaction_Transaction $transaction
    ) {
        $quote->collectTotals();
        $grandTotal = $quote->getGrandTotal();
        $quoteAmount = (int)round($this->formatPrice($grandTotal) * 100);
        $vippsAmount = (int)$transaction->getTransactionSummary()->getRemainingAmountToCapture();

        if ($quoteAmount != $vippsAmount) {
            throw new Vipps_Payment_Gateway_Exception_WrongAmountException(
                __("Quote Grand Total {$quoteAmount} does not match Transaction Amount {$vippsAmount}")
            );
        }
    }
}
