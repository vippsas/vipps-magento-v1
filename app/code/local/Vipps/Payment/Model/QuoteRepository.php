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

namespace Vipps\Payment\Model;

use Vipps\Payment\Model\Adapter\Quote\Factory;

/**
 * Class QuoteRepository
 */
class QuoteRepository
{
    /**
     * @var Factory
     */
    private $quoteFactory;

    /**
     * QuoteRepository constructor.
     */
    public function __construct()
    {
        $this->quoteFactory = new Factory();
    }

    /**
     * Save monitoring record
     *
     * @param \Vipps_Payment_Model_Quote $quote
     * @return \Vipps_Payment_Model_Quote
     * @throws \Mage_Core_Exception
     */
    public function save(\Vipps_Payment_Model_Quote $quote)
    {
        try {
            $quote->save();

            return $quote;
        } catch (\Exception $e) {
            throw new \Mage_Core_Exception(__('Could not save Vipps Quote: %s', $e->getMessage()));
        }
    }

    /**
     * Load monitoring quote by quote.
     *
     * @param $quoteId
     * @return false|\Mage_Core_Model_Abstract
     * @throws \Mage_Core_Exception
     */
    public function loadByQuote($quoteId)
    {
        $monitoringQuote = $this->quoteFactory->create();

        $monitoringQuote->load($quoteId, 'quote_id');

        if (!$monitoringQuote->getId()) {
            throw new \Mage_Core_Exception(__('No such entity with quote_id = %s', $quoteId));
        }

        return $monitoringQuote;
    }

    /**
     * @param int $monitoringQuoteId
     * @return \Vipps_Payment_Model_Quote
     * @throws \Mage_Core_Exception
     */
    public function load($monitoringQuoteId)
    {
        $monitoringQuote = $this->quoteFactory->create();

        $monitoringQuote->load($monitoringQuoteId);

        if (!$monitoringQuote->getId()) {
            throw new \Mage_Core_Exception(__('No such entity with entity_id = %s', $monitoringQuoteId));
        }

        return $monitoringQuote;
    }
}
