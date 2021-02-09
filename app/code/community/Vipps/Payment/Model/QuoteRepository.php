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
 * Class QuoteRepository
 */
class Vipps_Payment_Model_QuoteRepository
{
    /**
     * @var \Vipps_Payment_Model_Adapter_QuoteFactory
     */
    private $quoteFactory;

    /**
     * QuoteRepository constructor.
     */
    public function __construct()
    {
        $this->quoteFactory = Mage::getSingleton('vipps_payment/adapter_quoteFactory');
    }

    /**
     * Save monitoring record
     *
     * @param \Vipps_Payment_Model_Quote $quote
     * @return \Vipps_Payment_Model_Quote
     * @throws Mage_Core_Exception
     */
    public function save(\Vipps_Payment_Model_Quote $quote)
    {
        try {
            $quote->save();

            return $quote;
        } catch (\Exception $e) {
            throw new Mage_Core_Exception(__('Could not save Vipps Quote: %s', $e->getMessage()));
        }
    }

    /**
     * @param int $vippsQuoteId
     * @return \Vipps_Payment_Model_Quote
     * @throws Mage_Core_Exception
     */
    public function load($vippsQuoteId)
    {
        $vippsQuote = $this->quoteFactory->create();

        $vippsQuote->load($vippsQuoteId);

        if (!$vippsQuote->getId()) {
            throw new Mage_Core_Exception(__('No such entity with entity_id = %s', $vippsQuoteId));
        }

        return $vippsQuote;
    }

    /**
     * @param $reservedOrderId
     *
     * @return Vipps_Payment_Model_Quote
     * @throws Mage_Core_Exception
     */
    public function loadByReservedOrderId($reservedOrderId)
    {
        $vippsQuote = $this->quoteFactory->create();
        $vippsQuote->load($reservedOrderId, 'reserved_order_id');

        if (!$vippsQuote->getId()) {
            throw new Mage_Core_Exception(__('No such entity with reserved_order_id = %s', $reservedOrderId));
        }

        return $vippsQuote;
    }
}
