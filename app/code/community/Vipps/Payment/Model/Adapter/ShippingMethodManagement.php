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
 * Shipping method read service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vipps_Payment_Model_Adapter_ShippingMethodManagement
{
    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param \Varien_Object $address
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public function estimateByExtendedAddress(Mage_Sales_Model_Quote $quote, \Varien_Object $address)
    {
        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }
        return $this->getShippingMethods($quote, $address);
    }

    /**
     * Get list of available shipping methods
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param \Varien_Object $address
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    private function getShippingMethods(Mage_Sales_Model_Quote $quote, \Varien_Object $address)
    {
        $output = [];
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->addData($address->getData());
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectTotals();

        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $output[] = $this->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
            }
        }
        return $output;
    }

    /**
     * @return Mage_Directory_Model_Currency
     * @throws Mage_Core_Model_Store_Exception
     */
    private function getBaseCurrency()
    {
        return Mage::app()->getStore()->getBaseCurrency();
    }


    /**
     * Converts a specified rate model to a shipping method data object.
     *
     * @param Mage_Sales_Model_Quote_Address_Rate $rate The rate model.
     * @param string $quoteCurrencyCode The quote currency code.
     * @return \Varien_Object Shipping method data object.
     * @throws Mage_Core_Model_Store_Exception
     */
    public function modelToDataObject(Mage_Sales_Model_Quote_Address_Rate $rate, $quoteCurrencyCode)
    {
        /** @var Mage_Directory_Model_Currency $currency */
        $currency = $this->getBaseCurrency();

        $dataObject = new \Varien_Object();
        $amount = (float)$rate->getPrice();
        $errorMessage = $rate->getErrorMessage();
        $amountExclTax = Mage::helper('tax')->getShippingPrice($amount, false, $rate->getAddress());
        $amountInclTax = Mage::helper('tax')->getShippingPrice($amount, true, $rate->getAddress());

        $dataObject
            ->setCarrierCode($rate->getCarrier())
            ->setMethodCode($rate->getMethod())
            ->setCarrierTitle($rate->getCarrierTitle())
            ->setMethodTitle($rate->getMethodTitle())
            ->setAmount($currency->convert($rate->getPrice(), $quoteCurrencyCode))
            ->setBaseAmount($rate->getPrice())
            ->setAvailable(empty($errorMessage))
            ->setErrorMessage(empty($errorMessage) ? false : $errorMessage)
            ->setPriceExclTax($currency->convert($amountExclTax, $quoteCurrencyCode))
            ->setPriceInclTax($currency->convert($amountInclTax, $quoteCurrencyCode));

        return $dataObject;
    }
}
