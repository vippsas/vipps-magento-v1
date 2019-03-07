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
 * Class Item
 */
class Vipps_Payment_Gateway_Transaction_TransactionLogHistory_Item extends \Varien_Object
{
    /**
     * @var string
     */
    const AMOUNT = 'amount';

    /**
     * @var string
     */
    const OPERATION = 'operation';

    /**
     * @var bool
     */
    const OPERATION_SUCCESS = 'operationSuccess';

    /**
     * @var string
     */
    const REQUEST_ID = 'requestId';

    /**
     * @var string
     */
    const TIME_STAMP = 'timeStamp';

    /**
     * @var string
     */
    const TRANSACTION_ID = 'transactionId';

    /**
     * @var string
     */
    const TRANSACTION_TEXT = 'transactionText';

    /**
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return strtolower($this->getData(self::OPERATION));
    }

    /**
     * @return bool
     */
    public function isOperationSuccess()
    {
        return (bool)$this->getData(self::OPERATION_SUCCESS);
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return $this->getData(self::REQUEST_ID);
    }

    /**
     * @return string
     */
    public function getTimeStamp()
    {
        return $this->getData(self::TIME_STAMP);
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getData(self::TRANSACTION_ID);
    }

    /**
     * @return string
     */
    public function getTransactionText()
    {
        return $this->getData(self::TRANSACTION_TEXT);
    }

    /**
     * Return date when item was created
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setCreatedAt($value)
    {
        return $this->setData(self::CREATED_AT, $value);
    }

}
