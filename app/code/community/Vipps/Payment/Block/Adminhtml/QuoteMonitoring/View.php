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


class Vipps_Payment_Block_Adminhtml_QuoteMonitoring_View extends Mage_Adminhtml_Block_Template
{
    /**
     * @var Vipps_Payment_Model_Adapter_CartRepository
     */
    private $quoteRepository;

    /**
     * @var string
     */
    private $quoteLoadingError = '';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Mage_Core_Helper_Data
     */
    private $priceHelper;

    /**
     * @var Attempt_
     */
    private $attemptRepository;
    /**
     * @var Status
     */
    private $status;

    public function __construct(array $args = array())
    {
        parent::__construct($args);

        $this->quoteRepository = Mage::getSingleton('vipps_payment/adapter_cartRepository');
        $this->priceHelper = Mage::helper('core');
        $this->status = Mage::getResourceSingleton('vipps_payment/quote_status');
    }

    /**
     * @return Mage_Core_Helper_Data
     */
    public function getPriceHelper()
    {
        return $this->priceHelper;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        try {
            return $this->quoteRepository->get($this->getVippsQuote()->getQuoteId());
        } catch (\Exception $e) {
            $this->quoteLoadingError = $e->getMessage();
        }
    }

    /**
     * @return Vipps_Payment_Model_Quote
     */
    public function getVippsQuote()
    {
        return Mage::registry('vipps_quote');
    }

    /**
     * Quote loading error.
     *
     * @return string
     */
    public function getQuoteLoadingError()
    {
        return $this->quoteLoadingError;
    }

    /**
     * @return Vipps_Payment_Model_Resource_Quote_Attempt_Collection
     */
    public function getAttempts()
    {
        return Mage::getModel('vipps_payment/quote_attempt')
            ->getCollection()
            ->addFieldToFilter('parent_id', ['eq' => $this->getVippsQuote()->getEntityId()])
            ->setOrder('created_at')
            ->load();
    }

    /**
     * @param string $code
     * @return string
     */
    public function getStatusLabel($code)
    {
        return $this->status->getLabel($code);
    }

    /**
     * Retrieve formatting date
     *
     * @param null|string|\DateTimeInterface $date
     * @param int $format
     * @param bool $showTime
     * @param null|string $timezone
     * @return string
     */
    public function formatDate(
        $date = null,
        $format = IntlDateFormatter::MEDIUM,
        $showTime = true,
        $timezone = null
    ) {
        return parent::formatDate($date, $format, $showTime, $timezone);
    }
}
