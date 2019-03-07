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
 * @package Vipps\Payment\Model\Helper
 */
class Vipps_Payment_Model_QuoteUpdater
{
    /**
     * @var Vipps_Payment_Model_Adapter_CartRepository
     */
    private $cartRepository;

    /**
     * @var Vipps_Payment_Gateway_Command_PaymentDetailsProvider
     */
    private $paymentDetailsProvider;

    /**
     * @var Vipps_Payment_Gateway_Transaction_TransactionBuilder
     */
    private $transactionBuilder;
    /**
     * @var Vipps_Payment_Model_Helper_Utility
     */
    private $utility;

    /**
     * QuoteUpdater constructor.
     *
     */
    public function __construct()
    {
        $this->cartRepository = Mage::getSingleton('vipps_payment/adapter_cartRepository');
        $this->paymentDetailsProvider = new Vipps_Payment_Gateway_Command_PaymentDetailsProvider();
        $this->transactionBuilder = new Vipps_Payment_Gateway_Transaction_TransactionBuilder();
        $this->utility = Mage::getSingleton('vipps_payment/helper_utility');
    }

    /**
     * @param \Mage_Sales_Model_Quote $quote
     *
     * @return \Mage_Sales_Model_Quote
     * @throws Exception
     */
    public function execute(\Mage_Sales_Model_Quote $quote)
    {
        $response = $this->paymentDetailsProvider->get(['orderId' => $quote->getReservedOrderId()]);
        $transaction = $this->transactionBuilder->setData($response)->build();
        if (!$transaction->isExpressCheckout()) {
            return false;
        }
        $quote->setMayEditShippingAddress(false);
        $quote->setMayEditShippingMethod(true);

        $this->updateQuoteAddress($quote, $transaction);
        $this->utility->disabledQuoteAddressValidation($quote);
        $quote->collectTotals();
        $this->cartRepository->save($quote);
        return $quote;
    }

    /**
     * @param \Mage_Sales_Model_Quote $quote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     */
    private function updateQuoteAddress(\Mage_Sales_Model_Quote $quote, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        if (!$quote->getIsVirtual()) {
            $this->updateShippingAddress($quote, $transaction);
        }

        $this->updateBillingAddress($quote, $transaction);
    }

    /**
     * @param \Mage_Sales_Model_Quote $quote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     */
    private function updateShippingAddress(\Mage_Sales_Model_Quote $quote, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        $userDetails = $transaction->getUserDetails();
        $shippingDetails = $transaction->getShippingDetails();
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setLastname($userDetails->getLastName());
        $shippingAddress->setFirstname($userDetails->getFirstName());
        $shippingAddress->setEmail($userDetails->getEmail());
        $shippingAddress->setTelephone($userDetails->getMobileNumber());
        $shippingAddress->setShippingMethod($shippingDetails->getShippingMethodId());
        $shippingAddress->setShippingAmount($shippingDetails->getShippingCost());
        $shippingAddress->setCollectShippingRates(true);

        // try to obtain postCode one more time if it is not done before
        if (!$shippingAddress->getPostcode() && $shippingDetails->getPostcode()) {
            $shippingAddress->setPostcode($shippingDetails->getPostcode());
        }

        //We do not save user address from vipps in Magento
        $shippingAddress->setSaveInAddressBook(false);
        $shippingAddress->setSameAsBilling(true);
        $shippingAddress->unsCustomerAddressId();
    }

    /**
     * @param \Mage_Sales_Model_Quote $quote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     */
    private function updateBillingAddress(\Mage_Sales_Model_Quote $quote, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        $userDetails = $transaction->getUserDetails();
        $billingAddress = $quote->getBillingAddress();
        $shippingDetails = $transaction->getShippingDetails();

        $billingAddress->setLastname($userDetails->getLastName());
        $billingAddress->setFirstname($userDetails->getFirstName());
        $billingAddress->setEmail($userDetails->getEmail());
        $billingAddress->setTelephone($userDetails->getMobileNumber());

        // try to obtain postCode one more time if it is not done before
        if (!$billingAddress->getPostcode() && $shippingDetails->getPostcode()) {
            $billingAddress->setPostcode($shippingDetails->getPostcode());
        }

        //We do not save user address from vipps in Magento
        $billingAddress->setSaveInAddressBook(false);
        $billingAddress->setSameAsBilling(false);
        $billingAddress->unsCustomerAddressId();
    }
}
