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
 * Class QuoteLocator
 */
class Vipps_Payment_Model_QuoteLocator
{
    /**
     * @var Vipps_Payment_Model_QuoteRepository
     */
    private $quoteRepository;

    /**
     * QuoteManagement constructor.
     */
    public function __construct()
    {
        $this->quoteRepository = Mage::getSingleton('vipps_payment/quoteRepository');
    }

    /**
     * Retrieve a quote by increment id
     *
     * @param string $incrementId
     *
     * @return Mage_Sales_Model_Quote
     */
    public function get($incrementId)
    {
        $vippsQuote = $this->quoteRepository->loadByReservedOrderId($incrementId);

        if (!$vippsQuote->getQuoteId()) {
            return null;
        }

        $quote = Mage::getModel('sales/quote')->load($vippsQuote->getQuoteId());

        if (!$quote->getId()) {
            return null;
        }

        return $quote;
    }
}
