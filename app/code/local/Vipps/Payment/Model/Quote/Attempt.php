<?php
/**
 * Copyright 2018 Vipps
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 *  documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 *  the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 *  and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 *  TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 *  THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 *  CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 *  IN THE SOFTWARE.
 *
 */

/**
 * Quote cancellation model.
 */
class Vipps_Payment_Model_Quote_Attempt extends \Mage_Core_Model_Abstract implements Vipps_Payment_Model_Quote_QuoteAttemptInterface
{
    /**
     * @param int $parentId
     * @return self
     */
    public function setParentId($parentId)
    {
        return $this->setData(self::PARENT_ID, $parentId);
    }

    /**
     * @param string $message
     * @return self
     */
    public function setMessage( $message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * @param string $createdAt
     * @return self
     */
    public function setCreatedAt( $createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->getData(self::PARENT_ID);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init('vipps_payment/quote_attempt');
    }
}
