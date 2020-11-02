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
 * Class TransactionLogHistory
 */
class Vipps_Payment_Gateway_Transaction_TransactionLogHistory extends \Varien_Object
{
    /**
     * @var string
     */
    const ITEMS = 'items';
    /**
     * @var Vipps_Payment_Gateway_Transaction_TransactionLogHistory_Item
     */
    private $lastItem;

    /**
     * @return Vipps_Payment_Gateway_Transaction_TransactionLogHistory_Item[]
     */
    public function getItems()
    {
        return (array)$this->getData(self::ITEMS);
    }

    /**
     * Method to get Last Transaction Id from Transaction History.
     *
     * @return null|string
     */
    public function getLastTransactionId()
    {
        $transactionId = null;
        if ($this->getLastSuccessItem()) {
            $transactionId = $this->getLastSuccessItem()->getTransactionId();
        }

        return $transactionId;
    }

    /**
     * Method to return last Item.
     *
     * @return Item|null
     */
    public function getLastSuccessItem()
    {
        if (!$this->lastSuccessItem) {
            $items = $this->getItems();
            $lastTransactionTime = 0;
            foreach ($items as $item) {
                if ($item->isOperationSuccess() && $item->getTimeStamp() >= $lastTransactionTime) {
                    $lastTransactionTime = $item->getTimeStamp();
                    $this->lastSuccessItem = $item;
                }
            }
        }

        return $this->lastSuccessItem;
    }

    /**
     * @param string $operation
     *
     * @return Item|null
     */
    public function findItem($operation)
    {
        foreach ($this->getItems() as $item) {
            if ($item->getOperation() == $operation) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param string $operation
     *
     * @return Item|null
     */
    public function findSuccessItemWithOperation($operation)
    {
        foreach ($this->getItems() as $item) {
            if ($item->getOperation() == $operation && $item->isOperationSuccess()) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Method to return last Item.
     *
     * @return Vipps_Payment_Gateway_Transaction_TransactionLogHistory_Item
     */
    public function getLastItem()
    {
        if (!$this->lastItem) {
            $items = $this->getItems();
            $lastTransactionTime = 0;
            foreach ($items as $item) {
                if ($item->getTimeStamp() >= $lastTransactionTime) {
                    $lastTransactionTime = $item->getTimeStamp();
                    $this->lastItem = $item;
                }
            }
        }
        return $this->lastItem;
    }
}
