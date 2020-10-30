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
        try {
            $attemptMessage = '';
            $this->authorize();
            $quote = $this->getQuote();
            $vippsQuote = $this->vippsQuoteManagement->getByQuote($quote);

            $transaction = $this->transactionProcessor->process($vippsQuote);
            $redirectPath = $this->prepareResponse($vippsQuote, $transaction);

        } catch (Vipps_Payment_Model_Exception_AcquireLock $e) {
            $this->logger->critical($e->getMessage());
            if (!$this->updateCheckoutSession()) {
                $redirectPath = 'checkout/onepage/failure';
            }
        } catch (Vipps_Payment_Model_Exception_TransactionExpired $e) {
            $this->messageManager->addErrorMessage(
                __('Transaction was expired. Please, place your order again')
            );
            if (!$this->updateCheckoutSession()) {
                $redirectPath = 'checkout/onepage/failure';
            }
        } catch (Mage_Core_Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            if (!$this->updateCheckoutSession()) {
                $redirectPath = 'checkout/onepage/failure';
            }
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred during payment status update.'));
            if (!$this->updateCheckoutSession()) {
                $redirectPath = 'checkout/onepage/failure';
            }
        } finally {
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
            $this->order = $this->orderLocator->getByIncrement($this->getRequest()->getParam('order_id'));
        }

        return $this->order;
    }

    /**
     * @param Vipps_Payment_Model_Quote $vippsQuote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return string
     */
    private function prepareResponse(
        Vipps_Payment_Model_Quote $vippsQuote,
        Vipps_Payment_Gateway_Transaction_Transaction $transaction
    ) {
        $redirectUrl = 'checkout/onepage/success';
        if ($transaction->transactionWasCancelled()) {
            $this->messageManager->addErrorMessage(__('Your order was cancelled in Vipps.'));
        } elseif ($transaction->isTransactionExpired()) {
            $this->messageManager->addErrorMessage(
                __('Transaction was expired. Please, place your order again')
            );
        } elseif ($transaction->isTransactionReserved()) {
            $this->updateCheckoutSession();
            return $redirectUrl;
        } else {
            $this->messageManager->addErrorMessage(
                __('We have not received a confirmation that order was reserved. It will be checked later again.')
            );
        }

        $this->updateCheckoutSession();

        return $redirectUrl;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return bool
     */
    private function updateCheckoutSession()
    {
        $order = $this->getOrder();
        $quote = $this->getQuote();
        if ($order && $quote) {
            $this->checkoutSession->setLastQuoteId($quote->getId());
            $this->checkoutSession
                ->setLastSuccessQuoteId($quote->getId())
                ->setLastOrderId($order->getEntityId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            return true;
        }

        return false;
    }
}
