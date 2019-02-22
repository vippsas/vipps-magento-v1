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

namespace Vipps\Payment\Model\Quote;

use Vipps\Payment\Gateway\Transaction\ShippingDetails;
use Vipps\Payment\Model\Helper\Utility;

/**
 * Class AddressUpdater
 * @package Vipps\Payment\Model\Quote
 */
class AddressUpdater
{
    /**
     * @var Utility
     */
    private $utility;

    /**
     * AddressUpdater constructor.
     */
    public function __construct()
    {
        $this->utility = new Utility();
    }

    /**
     * Update quote addresses from source address.
     *
     * @param \Mage_Sales_Model_Quote $quote
     * @param \Mage_Sales_Model_Quote_Address $sourceAddress
     * @throws \Exception
     */
    public function fromSourceAddress(\Mage_Sales_Model_Quote $quote, \Varien_Object $sourceAddress)
    {
        $quote->setMayEditShippingAddress(false);
        $this->utility->disabledQuoteAddressValidation($quote);
        $this->updateQuoteAddresses($quote, $sourceAddress);
    }

    /**
     * Update quote addresses from source address.
     *
     * @param \Mage_Sales_Model_Quote $quote
     * @param \Mage_Sales_Model_Quote_Address $sourceAddress
     * @throws \Exception
     */
    private function updateQuoteAddresses(\Mage_Sales_Model_Quote $quote, \Mage_Sales_Model_Quote_Address $sourceAddress)
    {
        if (!$quote->getIsVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            $this->updateAddress($shippingAddress, $sourceAddress);
        }

        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setSameAsBilling(false);
        $this->updateAddress($billingAddress, $sourceAddress);
    }

    /**
     * Update destination address from source.
     *
     * @param \Mage_Sales_Model_Quote_Address $destAddress
     * @param \Mage_Sales_Model_Quote_Address $sourceAddress
     */
    private function updateAddress(\Mage_Sales_Model_Quote_Address $destAddress, \Mage_Sales_Model_Quote_Address $sourceAddress)
    {
        $destAddress
            ->setStreet($sourceAddress->getStreet())
            ->setCity($sourceAddress->getCity())
            ->setCountryId(ShippingDetails::NORWEGIAN_COUNTRY_ID)
            ->setPostcode($sourceAddress->getPostcode())
            ->setSaveInAddressBook(false)
            ->setSameAsBilling(true)
            ->setCustomerAddressId(null)
            ->save();
    }
}
