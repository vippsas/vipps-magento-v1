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
 * Class TransactionTextDataBuilder
 * @package Vipps\Payment\Gateway\Request
 */
class Vipps_Payment_Gateway_Request_TransactionTextDataBuilder extends Vipps_Payment_Gateway_Request_AbstractBuilder
{
    /**
     * Transaction block name
     *
     * @var string
     */
    private static $transaction = 'transaction';

    /**
     * Transaction text that can be displayed to end user. Value must be less than or equal to 100 characters.
     *
     * @var string
     */
    private static $transactionText = 'transactionText';

    /**
     * Get merchant related data for transaction request.
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $transactionData[self::$transaction][self::$transactionText] = $this->getTransactionText($buildSubject);
        return $transactionData;
    }

    /**
     * @param $buildSubject
     * @return string
     */
    private function getTransactionText($buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $storeName = $this->getStoreName();
        $text = $storeName ? __(
            'Thank you for shopping at %1.',
            $storeName
        ) : __(
            'Thank you for shopping.',
            $storeName
        );
        $transactionText[] = $text;

        if ($paymentDO) {
            $text = __('Order Id: %1', $paymentDO->getOrder()->getOrderIncrementId());
            $transactionText[] = $text;
        }

        return implode(' ', $transactionText);
    }

    /**
     * @return mixed
     */
    private function getStoreName()
    {
        return \Mage::getStoreConfig('general/store_information/name');
    }
}
