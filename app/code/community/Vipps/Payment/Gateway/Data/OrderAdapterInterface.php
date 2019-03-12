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

interface Vipps_Payment_Gateway_Data_OrderAdapterInterface
{
    /**
     * Returns currency code
     *
     * @return string
     */
    public function getCurrencyCode();

    /**
     * Returns order increment id
     *
     * @return string
     */
    public function getOrderIncrementId();

    /**
     * Returns customer ID
     *
     * @return int|null
     */
    public function getCustomerId();

    /**
     * Returns billing address
     *
     * @return AddressAdapterInterface|null
     */
    public function getBillingAddress();

    /**
     * Returns shipping address
     *
     * @return AddressAdapterInterface|null
     */
    public function getShippingAddress();

    /**
     * Returns order store id
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Returns order id
     *
     * @return int
     */
    public function getId();

    /**
     * Returns order grand total amount
     *
     * @return float
     */
    public function getGrandTotalAmount();

    /**
     * Returns list of line items in the cart
     *
     * @return array
     */
    public function getItems();

    /**
     * Gets the remote IP address for the order.
     *
     * @return string|null Remote IP address.
     */
    public function getRemoteIp();
}
