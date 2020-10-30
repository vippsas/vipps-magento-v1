<?php
/**
 *  Copyright © Vaimo Norge AS. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Class Regular
 * @package Vipps\Payment\Controller\Payment
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vipps_Payment_Payment_KlarnaController extends \Vipps_Payment_Controller_Abstract
{
    /**
     * @var Mage_Checkout_Model_Session;
     */
    private $checkoutSession;

    /**
     * @var Mage_Customer_Model_Session
     */
    private $customerSession;

    /**
     * @var Mage_Checkout_Helper_Data
     */
    protected $helper;

    /**
     * @var \Vipps_Payment_Model_Adapter_CartManagement
     */
    private $cartManagement;

    /**
     * @var \Vipps_Payment_Model_Adapter_CartRepository
     */
    private $cartRepository;

    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->checkoutSession = Mage::getSingleton('checkout/session');
        $this->customerSession = Mage::getSingleton('customer/session');
        $this->helper = Mage::helper('checkout');
        $this->cartManagement = Mage::getSingleton('vipps_payment/adapter_cartManagement');
        $this->cartRepository = Mage::getSingleton('vipps_payment/adapter_cartRepository');

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     */
    public function indexAction()
    {
        try {
            $quote = $this->checkoutSession->getQuote();

            $responseData = $this
                ->commandManager
                ->initiatePayment(
                    $quote->getPayment(), [
                        'amount' => $quote->getGrandTotal(),
                        Vipps_Payment_Gateway_Request_Initiate_InitiateBuilderInterface::PAYMENT_TYPE_KEY
                        => Vipps_Payment_Gateway_Request_Initiate_InitiateBuilderInterface::PAYMENT_TYPE_REGULAR_PAYMENT
                    ]
                );

            if (!isset($responseData['url'])) {
                throw new \Exception('Can\'t retrieve redirect URL.');
            }

            $this->cartManagement->placeOrder($quote->getId());

            return $this->_redirectUrl($responseData['url']);
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

    private function placeOrder(Mage_Sales_Model_Quote $quote)
    {
        $quote->getPayment()->setAdditionalInformation(
            Vipps_Payment_Model_Method_Vipps::METHOD_TYPE_KEY,
            Vipps_Payment_Model_Method_Vipps::METHOD_TYPE_REGULAR_CHECKOUT
        );

        $this->setCheckoutMethod($quote);
        $this->cartManagement->placeOrder($quote->getId());
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     */
    private function setCheckoutMethod(Mage_Sales_Model_Quote $quote)
    {
        if (!$quote->getCheckoutMethod()) {
            if ($this->customerSession->isLoggedIn()) {
                $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);
            } elseif ($this->helper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
            }
        }

        $this->cartRepository->save($quote);
    }
}
