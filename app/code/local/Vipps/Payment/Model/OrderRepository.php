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
 * Class OrderRepository
 * @package Vipps\Payment\Model\Adapter
 */
class Vipps_Payment_Model_OrderRepository
{
    /**
     * @param string $orderId
     * @param string|null $field
     * @return \Mage_Core_Model_Abstract
     * @throws \Mage_Core_Exception
     */
    public function get($orderId, $field = null)
    {
        $order = \Mage::getModel('sales/order')->load($orderId, $field);

        if (!$order->getId()) {
            \Mage::throwException('Order is not found');
        }

        return $order;
    }

    /**
     * @param \Mage_Sales_Model_Order $order
     * @return \Mage_Core_Model_Abstract
     * @throws \Exception
     */
    public function save(\Mage_Sales_Model_Order $order)
    {
        return $order->save();
    }

    /**
     * @param string $incrementId
     * @return null
     */
    public function getByIncrement($incrementId)
    {
        try {
            return $this->get($incrementId, 'increment_id');
        } catch (\Mage_Core_Exception $e) {
        }

        return null;
    }
}
