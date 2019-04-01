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

class Vipps_Payment_Gateway_Data_QuoteAdapter implements \Vipps_Payment_Gateway_Data_OrderAdapterInterface
{
    /**
     * @var Vipps_Payment_Model_Quote
     */
    private $quote;

    /**
     * @param Mage_Sales_Model_Quote $quote
     */
    public function __construct(
        Mage_Sales_Model_Quote $quote
    ) {
        $this->quote = $quote;
    }

    /**
     * Returns currency code
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->quote->getCurrency()->getBaseCurrencyCode();
    }

    /**
     * Returns order increment id
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->quote->getReservedOrderId();
    }

    /**
     * Returns customer ID
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->quote->getCustomer()->getId();
    }

    /**
     * Returns billing address
     *
     * @return AddressAdapterInterface|null
     */
    public function getBillingAddress()
    {
        if ($this->quote->getBillingAddress()) {
            return $this->quote->getBillingAddress();
        }

        return null;
    }

    /**
     * Returns shipping address
     *
     * @return AddressAdapterInterface|null
     */
    public function getShippingAddress()
    {
        if ($this->quote->getShippingAddress()) {
            return $this->quote->getShippingAddress();
        }

        return null;
    }

    /**
     * Returns order store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->quote->getStoreId();
    }

    /**
     * Returns order id
     *
     * @return int
     */
    public function getId()
    {
        return $this->quote->getId();
    }

    /**
     * Returns order grand total amount
     *
     * @return null
     */
    public function getGrandTotalAmount()
    {
        return null;
    }

    /**
     * Returns list of line items in the cart
     *
     * @return Mage_Sales_Model_Quote_Item[]|null
     */
    public function getItems()
    {
        return $this->quote->getItems();
    }

    /**
     * Gets the remote IP address for the order.
     *
     * @return string|null Remote IP address.
     */
    public function getRemoteIp()
    {
        return null;
    }
}
