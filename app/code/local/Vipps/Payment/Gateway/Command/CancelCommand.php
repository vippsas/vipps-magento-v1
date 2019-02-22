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

namespace Vipps\Payment\Gateway\Command;

use Vipps\Payment\Gateway\Exception\VippsException;
use Vipps\Payment\Gateway\Transaction\Transaction;
use Vipps\Payment\Gateway\Transaction\TransactionBuilder;
use Vipps\Payment\Gateway\Transaction\TransactionLogHistory\Item as TransactionLogHistoryItem;
use Vipps\Payment\Gateway\Transaction\TransactionSummary;
use Vipps\Payment\Model\Helper\Formatter;

/**
 * Class CancelCommand
 * @package Vipps\Payment\Gateway\Command
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CancelCommand extends GatewayCommand
{
    use Formatter;

    /**
     * @var PaymentDetailsProvider
     */
    private $paymentDetailsProvider;

    /**
     * @var TransactionBuilder
     */
    private $transactionBuilder;

    /**
     * CancelCommand constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct()
    {
        parent::__construct(
            new \Vipps\Payment\Gateway\Request\BuilderComposite\VippsCancelRequest(),
            new \Vipps\Payment\Gateway\Http\TransferFactory('PUT', '/ecomm/v2/payments/:orderId/cancel', ['orderId' => 'orderId']),
            new \Vipps\Payment\Gateway\Http\Client\Curl(),
            new \Vipps\Payment\Gateway\Response\TransactionHandler(),
            new \Vipps\Payment\Gateway\Validator\Composite\VippsGetPaymentDetailsValidator()
        );

        $this->paymentDetailsProvider = new PaymentDetailsProvider();
        $this->transactionBuilder = new TransactionBuilder();
    }

    /**
     * @param array $commandSubject
     * @return array|bool|ResultInterface|null
     * @throws VippsException
     */
    public function execute(array $commandSubject)
    {
        $response = $this->paymentDetailsProvider->get($commandSubject);
        $transaction = $this->transactionBuilder->setData($response)->build();

        // try to cancel based on payment details data
        if ($this->cancelBasedOnPaymentDetails($commandSubject, $transaction)) {
            return true;
        }

        if ($transaction->getTransactionSummary()->getCapturedAmount() > 0) {
            Mage::throwException(__('Can\'t cancel captured transaction.'));
        }

        // if previous cancel was failed - use the same request id
        $requestId = $this->getLastFailedRequestId($transaction);
        if ($requestId) {
            $commandSubject['requestId'] = $requestId;
        }

        return parent::execute($commandSubject);
    }

    /**
     * Try to cancel based on GetPaymentDetails service.
     *
     * @param $commandSubject
     * @param Transaction $transaction
     *
     * @return bool
     */
    private function cancelBasedOnPaymentDetails($commandSubject, Transaction $transaction)
    {
        $payment = $this->subjectReader->readPayment($commandSubject);

        $orderAdapter = $payment->getOrder();
        $orderIncrementId = $orderAdapter->getOrderIncrementId();

        $item = $this->findLatestSuccessHistoryItem($transaction);
        if ($item) {
            $responseBody = $this->prepareResponseBody($transaction, $item, $orderIncrementId);
            if ($this->handler) {
                $this->handler->handle($commandSubject, $responseBody);
            }
            return true;
        }

        return false;
    }

    /**
     * Get latest successful transaction log history item.
     *
     * @param Transaction $transaction
     *
     * @return null|TransactionLogHistoryItem
     */
    private function findLatestSuccessHistoryItem(Transaction $transaction)
    {
        foreach ($transaction->getTransactionLogHistory()->getItems() as $item) {
            $inContext = in_array(
                $item->getOperation(),
                [
                    Transaction::TRANSACTION_OPERATION_CANCEL,
                    Transaction::TRANSACTION_OPERATION_VOID
                ]
            );

            if ($inContext && $item->isOperationSuccess()) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Prepare response body based of GetPaymentDetails service data.
     *
     * @param Transaction $transaction
     * @param TransactionLogHistoryItem $item
     * @param $orderId
     *
     * @return array|null
     */
    private function prepareResponseBody(Transaction $transaction, TransactionLogHistoryItem $item, $orderId)
    {
        return [
            'orderId'            => $orderId,
            'transactionInfo'    => [
                'amount'          => $item->getAmount(),
                'status'          => Transaction::TRANSACTION_STATUS_CANCELLED,
                "timeStamp"       => $item->getTimeStamp(),
                "transactionId"   => $item->getTransactionId(),
                "transactionText" => $item->getTransactionText()
            ],
            'transactionSummary' => $transaction->getTransactionSummary()->toArray(
                [
                    TransactionSummary::CAPTURED_AMOUNT,
                    TransactionSummary::REMAINING_AMOUNT_TO_CAPTURE,
                    TransactionSummary::REFUNDED_AMOUNT,
                    TransactionSummary::REMAINING_AMOUNT_TO_REFUND
                ]
            )
        ];
    }

    /**
     * Retrieve request id of last failed operation from transaction log history.
     *
     * @param Transaction $transaction
     *
     * @return string|null
     */
    private function getLastFailedRequestId(Transaction $transaction)
    {
        foreach ($transaction->getTransactionLogHistory()->getItems() as $item) {
            $inContext = in_array(
                $item->getOperation(),
                [
                    Transaction::TRANSACTION_OPERATION_CANCEL,
                    Transaction::TRANSACTION_OPERATION_VOID
                ]
            );

            if (!$inContext) {
                continue;
            }
            if (true !== $item->isOperationSuccess()) {
                return $item->getRequestId();
            }
        }
        return null;
    }
}
