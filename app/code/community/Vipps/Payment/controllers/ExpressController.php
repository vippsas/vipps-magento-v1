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

class Vipps_Payment_ExpressController extends Vipps_Payment_Controller_Abstract
{
    /**
     * @var Mage_Checkout_Model_Session
     */
    protected $checkoutSession;

    /**
     * @var Mage_Checkout_Helper_Data
     */
    protected $checkoutHelper;

    public function preDispatch()
    {
        return parent::preDispatch();
        $this->checkoutHelper = Mage::helper('checkout');
    }

    public function indexAction()
    {
        try {
            if (!$this->config->getValue('express_checkout')) {
                throw new Mage_Core_Exception(__('Express Payment method is not available.'));
            }
            $quote = $this->cart->getQuote();
            $vippsUrl = $quote->getPayment()->getAdditionalInformation(
                Vipps_Payment_Model_Observer_CheckoutSubmitAllAfter::VIPPS_URL_KEY
            );

            if ($vippsUrl) {
                return $this->_redirectUrl($vippsUrl);
            }

            $quote->getPayment()->setAdditionalInformation(
                Vipps_Payment_Model_Method_Vipps::METHOD_TYPE_KEY,
                Vipps_Payment_Model_Method_Vipps::METHOD_TYPE_EXPRESS_CHECKOUT
            );

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setShippingMethod(null);
            $quote->collectTotals();

            $quote->getPayment()->setAdditionalInformation(
                Vipps_Payment_Model_Method_Vipps::METHOD_TYPE_KEY,
                Vipps_Payment_Model_Method_Vipps::METHOD_TYPE_EXPRESS_CHECKOUT
            );

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setShippingMethod(null);
            $quote->collectTotals();

            $quote->getPayment()->setAdditionalInformation(
                Vipps_Payment_Model_Method_Vipps::METHOD_TYPE_KEY,
                Vipps_Payment_Model_Method_Vipps::METHOD_TYPE_EXPRESS_CHECKOUT
            );

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setShippingMethod(null);
            $quote->collectTotals();

            $responseData = $this->commandManager->initiatePayment(
                $quote->getPayment(),
                [
                    'amount'
                        => $quote->getGrandTotal(),
                    Vipps_Payment_Gateway_Request_Initiate_InitiateBuilderInterface::PAYMENT_TYPE_KEY
                        => Vipps_Payment_Gateway_Request_Initiate_InitiateBuilderInterface::PAYMENT_TYPE_EXPRESS_CHECKOUT
                ]
            );
            $vippsUrl = $responseData['url'] ?? '';
            $quote->getPayment()->setAdditionalInformation(
                Vipps_Payment_Model_Observer_CheckoutSubmitAllAfter::VIPPS_URL_KEY,
                $vippsUrl
            );

            if (!$quote->getCheckoutMethod()) {
                if ($this->customerSession->isLoggedIn()) {
                    $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);
                } elseif ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                    $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
                } else {
                    $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
                }
            }
            $quote->setIsActive(true);
            $quote->save();


if (!isset($responseData['url'])) {
                throw new \Exception('Can\'t retrieve redirect URL.');
            }

            // save URL, so we can understand that we already have it for pay in case of some error
            $quote->getPayment()->setAdditionalInformation(
                Vipps_Payment_Model_Observer_CheckoutSubmitAllAfter::VIPPS_URL_KEY,
                $responseData['url']
            );

            if (!$quote->getCheckoutMethod()) {
                if ($this->customerSession->isLoggedIn()) {
                    $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);
                } elseif ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                    $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
                } else {
                    $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
                }
            }

            $quote->setIsActive(true);
            $quote->save();

            return $this->_redirectUrl($vippsUrl);
        } catch (Vipps_Payment_Gateway_Exception_VippsException $e) {
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

        return $this->_redirect('checkout/cart', ['_secure' => true]);

    }
}
