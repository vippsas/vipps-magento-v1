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
     * @var Mage_Checkout_Model_Session
     */
    private $checkoutSession;

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
     * @var Vipps_Payment_Model_TransactionProcessor
     */
    private $transactionProcessor;

    /**
     * @var Mage_Sales_Model_Quote
     */
    private $quote;

    /**
     * @var Mage_Sales_Model_Order
     */
    private $order;

    /**
     * @var Vipps_Payment_Model_Quote|null
     */
    private $vippsQuote = null;

    /**
     * @return $this|Mage_Core_Controller_Front_Action|\Vipps_Payment_Controller_Abstract
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->checkoutSession = Mage::getSingleton('checkout/session');
        $this->cartRepository = Mage::getSingleton('vipps_payment/adapter_cartRepository');
        $this->quoteLocator = Mage::getSingleton('vipps_payment/quoteLocator');
        $this->orderLocator = Mage::getSingleton('vipps_payment/orderRepository');
        $this->vippsQuoteManagement = Mage::getSingleton('vipps_payment/quoteManagement');
        $this->transactionProcessor = Mage::getSingleton('vipps_payment/transactionProcessor');

        return $this;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function indexAction()
    {
        $redirectPath = 'checkout/onepage/success';
        try {
            $this->authorize();

            $vippsQuote = $this->getVippsQuote();
            $transaction = $this->transactionProcessor->process($vippsQuote);

            $redirectPath = $this->prepareResponse($transaction);
        } catch (Vipps_Payment_Model_Exception_TransactionExpired $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('Transaction was expired. Please, place your order again')
            );
        } catch (Mage_Core_Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred during payment status update.'));
        } finally {
            $this->storeLastOrderOrRestoreQuote();
            if (isset($e)) {
                if ($this->getVippsQuote()->getOrderId()) {
                    $redirectPath = 'checkout/onepage/failure';
                } else {
                    $redirectPath = 'checkout/cart';
                }
            }
            $compliant = $this->gdprCompliance->process($this->getRequest()->getRequestString());
            $this->logger->debug($compliant);
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
     * @return bool|Mage_Sales_Model_Order
     */
    private function getOrder()
    {
        if (null === $this->order) {
            $this->order = $this->orderLocator->getById($this->getVippsQuote(true)->getOrderId());
        }

        return $this->order;
    }

    /**
     * @param Vipps_Payment_Model_Quote $vippsQuote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return string
     */
    private function prepareResponse(Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        if ($transaction->transactionWasCancelled()) {
            $this->messageManager->addErrorMessage(__('Your order was cancelled in Vipps.'));
        } elseif ($transaction->isTransactionExpired()) {
            $this->messageManager->addErrorMessage(__('Transaction was expired. Please, place your order again'));
        } elseif ($transaction->isTransactionReserved() || $transaction->isTransactionCaptured()) {
            return 'checkout/onepage/success';
        } else {
            $this->messageManager->addErrorMessage(
                __('We have not received a confirmation that order was reserved. It will be checked later again.')
            );
        }

        if ($this->getVippsQuote()->getOrderId()) {
            $redirectUrl = 'checkout/onepage/failure';
        } else {
            $redirectUrl = 'checkout/cart';
        }

        return $redirectUrl;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return bool
     */
    private function storeLastOrderOrRestoreQuote()
    {
        $order = $this->getOrder();
        $quote = $this->getQuote();
        $vippsQuote = $this->getVippsQuote(true);

        if ($vippsQuote->getOrderId()) {
            $this->checkoutSession
                ->setLastSuccessQuoteId($quote->getId())
                ->setLastQuoteId($quote->getId())
                ->setLastOrderId($order->getEntityId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());;
        } else {
            $quote = $this->cartRepository->get($vippsQuote->getQuoteId());
            $quote->setIsActive(true);
            $quote->setReservedOrderId(null);

            $this->cartRepository->save($quote);
            $this->checkoutSession->replaceQuote($quote);
        }
    }

    /**
     * @param $quote
     * @param bool $forceReload
     *
     * @return Vipps_Payment_Model_Quote|null
     * @throws Mage_Core_Exception
     */
    private function getVippsQuote($forceReload = false)
    {
        if (null === $this->vippsQuote || $forceReload) {
            $quote = $this->getQuote();
            $this->vippsQuote = $this->vippsQuoteManagement->getByQuote($quote);;
        }

        return $this->vippsQuote;
    }
}
