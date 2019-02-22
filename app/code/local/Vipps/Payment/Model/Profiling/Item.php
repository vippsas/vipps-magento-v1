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

use Vipps\Payment\Model\Profiling\ItemInterface;

/**
 * Class Item
 * @package Vipps\Payment\Model\Profiling
 */
class Vipps_Payment_Model_Profiling_Item extends \Mage_Core_Model_Abstract implements ItemInterface // Move in interface
{
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
     * Return increment id value
     *
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->getId();
    }

    /**
     * Set increment id value
     *
     * @param string $value
     */
    public function setEntityId($value)
    {
        $this->setData(self::ENTITY_ID, $value);
    }

    /**
     * Return increment id value
     *
     * @return string
     */
    public function getIncrementId()
    {
        return (string)$this->getData(self::INCREMENT_ID);
    }

    /**
     * Set increment id value
     *
     * @param string $value
     */
    public function setIncrementId($value)
    {
        $this->setData(self::INCREMENT_ID, $value);
    }

    /**
     * Return increment id value
     *
     * @return string
     */
    public function getStatusCode()
    {
        return (string)$this->getData(self::STATUS_CODE);
    }

    /**
     * Set increment id value
     *
     * @param string $value
     */
    public function setStatusCode($value)
    {
        $this->setData(self::STATUS_CODE, $value);
    }

    /**
     * Return request type value
     *
     * @return string
     */
    public function getRequestType()
    {
        return (string)$this->getData(self::REQUEST_TYPE);
    }

    /**
     * Set request type value
     *
     * @param string $value
     */
    public function setRequestType($value)
    {
        $this->setData(self::REQUEST_TYPE, $value);
    }

    /**
     * Return request value
     *
     * @return string
     */
    public function getRequest()
    {
        return (string)$this->getData(self::REQUEST);
    }

    /**
     * Set request value
     *
     * @param string $value
     */
    public function setRequest($value)
    {
        $this->setData(self::REQUEST, $value);
    }

    /**
     * Return formatted request value
     *
     * @return string
     */
    public function getFormattedRequest()
    {
        return $this->formatHtml($this->getData(self::REQUEST));
    }

    /**
     * Return formatted value, replace map is:
     *  - whitespace to '$nbsp; '
     *  - apply 'nl2br'
     *  - apply 'htmlspecialchars'
     *
     * @param $value
     * @return string
     */
    private function formatHtml($value)
    {
        return nl2br(str_replace(' ', '&nbsp; ', htmlspecialchars($value)));
    }

    /**
     * Return response value
     *
     * @return string
     */
    public function getResponse()
    {
        return (string)$this->getData(self::RESPONSE);
    }

    /**
     * Set response value
     *
     * @param string $value
     */
    public function setResponse($value)
    {
        $this->setData(self::RESPONSE, $value);
    }

    /**
     * Return formatted response value
     *
     * @return string
     */
    public function getFormattedResponse()
    {
        return $this->formatHtml($this->getData(self::RESPONSE));
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init('vipps_payment/profiling_item');
    }
}
