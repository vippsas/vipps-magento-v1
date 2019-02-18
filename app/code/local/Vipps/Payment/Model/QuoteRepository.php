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

namespace Vipps\Payment\Model\Adapter;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Vipps\Payment\Model\Adapter\Adapter\Quote\Factory;
use Vipps\Payment\Model\Adapter\Adapter\Resource;

/**
 * Class QuoteRepository
 */
class QuoteRepository
{
    /**
     * @var Resource
     */
    private $quoteResource;

    /**
     * @var Factory
     */
    private $quoteFactory;

    /**
     * QuoteRepository constructor.
     */
    public function __construct()
    {
        $this->quoteResource = new Resource();
        $this->quoteFactory = new Factory();
    }

    /**
     * Save monitoring record
     *
     * @param \Vipps_Payment_Model_Quote $quote
     * @return \Vipps_Payment_Model_Quote
     */
    public function save(\Vipps_Payment_Model_Quote $quote)
    {
        try {
            $this->quoteResource->save($quote);

            return $quote;
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __(
                    'Could not save Vipps Quote: %1',
                    $e->getMessage()
                ),
                $e
            );
        }
    }

    /**
     * Load monitoring quote by quote.
     *
     * @param $quoteId
     * @return false|\Mage_Core_Model_Abstract
     * @throws NoSuchEntityException
     */
    public function loadByQuote($quoteId)
    {
        $monitoringQuote = $this->quoteFactory->create();

        $this->quoteResource->load($monitoringQuote, $quoteId, 'quote_id');

        if (!$monitoringQuote->getId()) {
            throw NoSuchEntityException::singleField('quote_id', $quoteId);
        }

        return $monitoringQuote;
    }

    /**
     * @param int $monitoringQuoteId
     * @return \Vipps_Payment_Model_Quote
     * @throws NoSuchEntityException
     */
    public function load($monitoringQuoteId)
    {
        $monitoringQuote = $this->quoteFactory->create();

        $this->quoteResource->load($monitoringQuote, $monitoringQuoteId);

        if (!$monitoringQuote->getId()) {
            throw NoSuchEntityException::singleField('entity_id', $monitoringQuoteId);
        }

        return $monitoringQuote;
    }
}
