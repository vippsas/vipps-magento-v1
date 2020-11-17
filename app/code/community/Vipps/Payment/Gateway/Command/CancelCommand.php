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
 * Class CancelCommand
 */
class Vipps_Payment_Gateway_Command_CancelCommand extends Vipps_Payment_Gateway_Command_GatewayCommand
{
    use Vipps_Payment_Model_Helper_Formatter;

    /**
     * @var Vipps_Payment_Gateway_Command_PaymentDetailsProvider
     */
    private $paymentDetailsProvider;

    /**
     * CancelCommand constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct()
    {
        parent::__construct(
            new Vipps_Payment_Gateway_Request_BuilderComposite_VippsCancelRequest(),
            new Vipps_Payment_Gateway_Http_TransferFactory('PUT', '/ecomm/v2/payments/:orderId/cancel', ['orderId' => 'orderId']),
            new Vipps_Payment_Gateway_Http_Client_Curl(),
            new Vipps_Payment_Gateway_Response_TransactionHandler(),
            new Vipps_Payment_Gateway_Validator_Composite_VippsGetPaymentDetailsValidator()
        );

        $this->paymentDetailsProvider = new Vipps_Payment_Gateway_Command_PaymentDetailsProvider();
    }

    /**
     * @param array $commandSubject
     * @return array|bool|Vipps_Payment_Gateway_Validator_Result|null
     * @throws Mage_Core_Exception
     */
    public function execute(array $commandSubject)
    {
        $transaction = $this->paymentDetailsProvider->get($commandSubject);

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
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return bool
     */
    private function cancelBasedOnPaymentDetails($commandSubject, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
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
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     *
     * @return null|Vipps_Payment_Gateway_Transaction_TransactionLogHistory_Item
     */
    private function findLatestSuccessHistoryItem(Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        foreach ($transaction->getTransactionLogHistory()->getItems() as $item) {
            $inContext = in_array(
                $item->getOperation(),
                [
                    Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_OPERATION_CANCEL,
                    Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_OPERATION_VOID
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
     * @param \Vipps_Payment_Gateway_Transaction_Transaction $transaction
     * @param Vipps_Payment_Gateway_Transaction_TransactionLogHistory_Item $item
     * @param $orderId
     *
     * @return array|null
     */
    private function prepareResponseBody(Vipps_Payment_Gateway_Transaction_Transaction $transaction, Vipps_Payment_Gateway_Transaction_TransactionLogHistory_Item $item, $orderId)
    {
        return [
            'orderId'            => $orderId,
            'transactionInfo'    => [
                'amount'          => $item->getAmount(),
                'status'          => Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_STATUS_CANCELLED,
                "timeStamp"       => $item->getTimeStamp(),
                "transactionId"   => $item->getTransactionId(),
                "transactionText" => $item->getTransactionText()
            ],
            'transactionSummary' => $transaction->getTransactionSummary()->toArray(
                [
                    Vipps_Payment_Gateway_Transaction_TransactionSummary::CAPTURED_AMOUNT,
                    Vipps_Payment_Gateway_Transaction_TransactionSummary::REMAINING_AMOUNT_TO_CAPTURE,
                    Vipps_Payment_Gateway_Transaction_TransactionSummary::REFUNDED_AMOUNT,
                    Vipps_Payment_Gateway_Transaction_TransactionSummary::REMAINING_AMOUNT_TO_REFUND
                ]
            )
        ];
    }

    /**
     * Retrieve request id of last failed operation from transaction log history.
     *
     * @param \Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return string|null
     */
    private function getLastFailedRequestId(Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        foreach ($transaction->getTransactionLogHistory()->getItems() as $item) {
            $inContext = in_array(
                $item->getOperation(),
                [
                    Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_OPERATION_CANCEL,
                    Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_OPERATION_VOID
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
