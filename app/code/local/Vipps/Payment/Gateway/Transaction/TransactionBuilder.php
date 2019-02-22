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

namespace Vipps\Payment\Gateway\Transaction;

/**
 * Class TransactionBuilder
 * @package Vipps\Payment\Gateway\Transaction
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionBuilder
{
    /**
     * @var array
     */
    private $response;

    /**
     * Set request to builder
     *
     * @param $response
     *
     * @return $this
     */
    public function setData($response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * build transaction object
     *
     * @return Transaction
     */
    public function build()
    {
        $infoData = isset($this->response['transactionInfo']) ?
            $this->response['transactionInfo']
            : (isset($this->response['transaction']) ? $this->response['transaction'] : []);

        $info = new TransactionInfo($infoData);

        $summaryData = isset($this->response['transactionSummary']) ? $this->response['transactionSummary'] : [];
        $summary = new TransactionSummary($summaryData);

        $logHistoryData = isset($this->response['transactionLogHistory']) ? $this->response['transactionLogHistory'] : [];
        $items = [];
        foreach ($logHistoryData as $itemData) {
            $items[] = new TransactionLogHistory\Item($itemData);
        }
        $logHistory = new TransactionLogHistory(['items' => $items]);

        $userDetails = null;
        if (isset($this->response['userDetails'])) {
            $userDetails = new UserDetails($this->response['userDetails']);
        }

        $shippingDetails = null;
        if (isset($this->response['shippingDetails'])) {
            $shippingDetails = new ShippingDetails($this->response['shippingDetails']);
        }

        return new Transaction($info, $summary, $logHistory, $userDetails, $shippingDetails);
    }
}
