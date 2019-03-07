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
 * Class Fallback
 * @package Vipps\Payment\Controller\Payment
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vipps_Payment_Payment_FallbackController extends \Vipps_Payment_Controller_Abstract
{
    /**
     * @var \Mage_Checkout_Model_Session
     */
    private $checkoutSession;

    /**
     * @var Vipps_Payment_Model_OrderPlace
     */
    private $orderPlace;

    /**
     * @var Vipps_Payment_Model_Adapter_CartRepository
     */
    private $cartRepository;

    /**
     * @var Vipps_Payment_Model_QuoteLocator
     */
    private $quoteLocator;

    /**
     * @var Vipps_Payment_Model_OrderRepository
     */
    private $orderLocator;

    /**
     * @var Vipps_Payment_Model_QuoteManagement
     */
    private $vippsQuoteManagement;

    /**
     * @var Vipps_Payment_Model_Quote_AttemptManagement
     */
    private $attemptManagement;

    /**
     * @var Mage_Sales_Model_Quote
     */
    private $quote;

    /**
     * @var \Mage_Sales_Model_Order
     */
    private $order;

    /**
     * @return $this|\Mage_Core_Controller_Front_Action|\Vipps_Payment_Controller_Abstract
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->checkoutSession = Mage::getSingleton('checkout/session');
        $this->orderPlace = Mage::getSingleton('vipps_payment/orderPlace');
        $this->cartRepository = Mage::getSingleton('vipps_payment/adapter_cartRepository');
        $this->quoteLocator = Mage::getSingleton('vipps_payment/quoteLocator');
        $this->orderLocator = Mage::getSingleton('vipps_payment/orderRepository');
        $this->vippsQuoteManagement = Mage::getSingleton('vipps_payment/quoteManagement');
        $this->attemptManagement = Mage::getSingleton('vipps_payment/quote_attemptManagement');

        return $this;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function indexAction()
    {
        try {
            $this->authorize();

            $quote = $this->getQuote();
            $order = $this->getOrder();
            $vippsQuote = $this->vippsQuoteManagement->getByQuote($quote);
            $vippsQuote->setStatus(Vipps_Payment_Model_QuoteStatusInterface::STATUS_PROCESSING);
            $attempt = $this->attemptManagement->createAttempt($vippsQuote);
            if (!$order) {
                $order = $this->placeOrder($quote, $vippsQuote, $attempt);
            }
            $attemptMessage = __('Placed');
            $vippsQuote->setStatus(Vipps_Payment_Model_QuoteStatusInterface::STATUS_PLACED);
            $this->updateCheckoutSession($quote, $order);
            $redirectPath = 'checkout/onepage/success';
        } catch (Mage_Core_Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            $attemptMessage = $e->getMessage();
            $redirectPath = 'checkout/onepage/failure';
        } catch (\Exception $e) {
            $attemptMessage = $e->getMessage();
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred during payment status update.'));
            $redirectPath = 'checkout/onepage/failure';
        } finally {
            $compliant = $this->gdprCompliance->process($this->getRequest()->getRequestString());
            $this->logger->debug($compliant);

            if (isset($attempt)) {
                $attempt->setMessage($attemptMessage);
                $this->attemptManagement->save($attempt);
                $this->vippsQuoteManagement->save($vippsQuote);
            }
        }
        $this->_redirect($redirectPath, ['_secure' => true]);
    }

    /**
     * Request authorization process
     *
     * @return bool
     * @throws Exception
     */
    private function authorize()
    {
        if (!$this->getRequest()->getParam('order_id')
            || !$this->getRequest()->getParam('access_token')
        ) {
            throw new Exception('Invalid request parameters');
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $this->getQuote();
        if ($quote) {
            $additionalInfo = $quote->getPayment()->getAdditionalInformation();

            $fallbackAuthToken = isset($additionalInfo[Vipps_Payment_Gateway_Request_Initiate_MerchantDataBuilder::FALLBACK_AUTH_TOKEN])
                ? $additionalInfo[Vipps_Payment_Gateway_Request_Initiate_MerchantDataBuilder::FALLBACK_AUTH_TOKEN]
                : null;
            $accessToken = $this->getRequest()->getParam('access_token', '');
            if ($fallbackAuthToken === $accessToken) {
                return true;
            }
        }

        throw new Exception('Invalid request');
    }

    /**
     * Retrieve quote from quote repository if no then from order
     *
     * @return Mage_Sales_Model_Quote|bool
     * @throws NoSuchEntityException
     */
    private function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->quoteLocator->get($this->getRequest()->getParam('order_id')) ?: false;
            if ($this->quote) {
                return $this->quote;
            }
            $order = $this->getOrder();
            if ($order) {
                $this->quote = $this->cartRepository->get($order->getQuoteId());
            } else {
                $this->quote = false;
            }
        }
        return $this->quote;
    }

    /**
     * Retrieve order object from repository based on increment id
     *
     * @return bool|\Mage_Sales_Model_Order
     */
    private function getOrder()
    {
        if (null === $this->order) {
            $this->order = $this->orderLocator->getByIncrement($this->getRequest()->getParam('order_id'));
        }
        return $this->order;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @param \Vipps_Payment_Model_Quote $vippsQuote
     * @param \Vipps_Payment_Model_Quote_Attempt $attempt
     * @return Mage_Sales_Model_Order|null
     * @throws Vipps_Payment_Gateway_Exception_MerchantException
     * @throws Mage_Core_Exception
     * @throws Vipps_Payment_Gateway_Exception_VippsException
     * @throws Vipps_Payment_Gateway_Exception_WrongAmountException
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function placeOrder(
        Mage_Sales_Model_Quote $quote,
        \Vipps_Payment_Model_Quote $vippsQuote,
        \Vipps_Payment_Model_Quote_Attempt $attempt)
    {
        try {
            $response = $this->commandManager->getOrderStatus(
                $this->getRequest()->getParam('order_id')
            );
            $transaction = $this->transactionBuilder->setData($response)->build();
            if ($transaction->isTransactionAborted()) {
                $attempt->setMessage('Transaction was cancelled in Vipps');
                $vippsQuote->setStatus(Vipps_Payment_Model_QuoteStatusInterface::STATUS_CANCELED);
                $this->restoreQuote();
            }
            $order = $this->orderPlace->execute($quote, $transaction);
            if (!$order) {
                Mage::throwException(__('Couldn\'t get information about order status right now. Please contact a store administrator.'));
            }
            $this->updateCheckoutSession($quote, $order);
            return $order;
        } catch (Vipps_Payment_Gateway_Exception_MerchantException $e) {
            //@todo workaround for vipps issue with order cancellation (delete this condition after fix) //@codingStandardsIgnoreLine
            if ($e->getCode() == Vipps_Payment_Gateway_Exception_MerchantException::ERROR_CODE_REQUESTED_ORDER_NOT_FOUND) {
                $this->restoreQuote();
            } else {
                throw $e;
            }
        }
    }

    /**
     * @throws Mage_Core_Exception
     */
    private function restoreQuote()
    {
        $quote = $this->getQuote();

        /** @var Mage_Sales_Model_Quote $quote */
        $quote->setIsActive(true);
        $quote->setReservedOrderId(null);
        $this->cartRepository->save($quote);

        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->replaceQuote($quote);
        Mage::throwException(__('Your order was canceled in Vipps.'));
    }

    /**
     * Method to update Checkout session for success page when order was placed with Callback Controller.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param \Mage_Sales_Model_Order $order
     */
    private function updateCheckoutSession(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Order $order = null)
    {
        $this->checkoutSession->setLastQuoteId($quote->getId());
        if ($order) {
            $this->checkoutSession
                ->setLastSuccessQuoteId($quote->getId())
                ->setLastOrderId($order->getEntityId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());
        }
    }
}
