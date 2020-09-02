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
 * Class Regular
 * @package Vipps\Payment\Controller\Payment
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vipps_Payment_Payment_RegularController extends \Vipps_Payment_Controller_Abstract
{
    /**
     * @var Mage_Checkout_Model_Session;
     */
    private $onePageCheckout;

    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->onePageCheckout = Mage::getSingleton('checkout/type_onepage');

        return $this;
    }
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function indexAction()
    {
        $responseData = [];
        try {
            $quote = $this->onePageCheckout->getQuote();
            $quote->hasItems();
            $vippsUrl = $quote->getPayment()->getAdditionalInformation(
                Vipps_Payment_Model_Observer_CheckoutSubmitAllAfter::VIPPS_URL_KEY
            );
            // we have to to such call only once per quote, in other case will get error
            if ($vippsUrl) {
                return $this->_renderJson(['url' => $vippsUrl]);
            }
            $responseData = $this
                ->commandManager
                ->initiatePayment(
                    $quote->getPayment(), [
                        'amount' => $quote->getGrandTotal(),
                        Vipps_Payment_Gateway_Request_Initiate_InitiateBuilderInterface::PAYMENT_TYPE_KEY
                        => Vipps_Payment_Gateway_Request_Initiate_InitiateBuilderInterface::PAYMENT_TYPE_REGULAR_PAYMENT
                    ]
                );
            $vippsUrl = $responseData['url'] ?? '';

            $quote->getPayment()->setAdditionalInformation(
                Vipps_Payment_Model_Method_Vipps::METHOD_TYPE_KEY,
                Vipps_Payment_Model_Method_Vipps::METHOD_TYPE_EXPRESS_CHECKOUT
            );
            // save URL, so we can understand that we already have it for pay in case of some error
            $quote->getPayment()->setAdditionalInformation(
                Vipps_Payment_Model_Observer_CheckoutSubmitAllAfter::VIPPS_URL_KEY,
                $vippsUrl
            );
            $quote->setIsActive(true);
            $quote->save();

            return $this->_renderJson(['url' => $vippsUrl]);
        } catch (Vipps_Payment_Gateway_Exception_VippsException $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this
                ->messageManager
                ->addErrorMessage(__('An error occurred during request to Vipps. Please try again later.'));
        }

        $this->_redirect('checkout/onepage');
    }
}
