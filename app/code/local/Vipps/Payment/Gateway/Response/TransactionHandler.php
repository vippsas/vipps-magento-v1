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
 * Class TransactionHandler
 */
class Vipps_Payment_Gateway_Response_TransactionHandler extends Vipps_Payment_Gateway_Response_HandlerAbstract
{
    /**
     * {@inheritdoc}
     *
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response) //@codingStandardsIgnoreLine
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        $transaction = $this->transactionBuilder
            ->setData($response)
            ->build();

        if ($payment) { // check for instance of.
            $status = $transaction->getTransactionInfo()->getStatus();
            $transactionId = $transaction->getTransactionInfo()->getTransactionId();

            if ($status == Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_STATUS_CANCELLED) {
                $transactionId .= '-void';
            }
            $payment->setTransactionId($transactionId);
            $payment->setTransactionAdditionalInfo(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                $transaction->getTransactionInfo()->getData() + $transaction->getTransactionSummary()->getData()
            );
        }
    }
}
