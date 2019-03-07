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
 * Class RefundCommand
 */
class Vipps_Payment_Gateway_Command_RefundCommand extends Vipps_Payment_Gateway_Command_GatewayCommand
{
    /**
     * @var \Vipps_Payment_Gateway_Command_PaymentDetailsProvider
     */
    private $paymentDetailsProvider;

    /**
     * @var Vipps_Payment_Gateway_Transaction_TransactionBuilder
     */
    private $transactionBuilder;

    /**
     * @var \Vipps_Payment_Model_OrderRepository
     */
    private $orderRepository;

    /**
     * RefundCommand constructor.
     */
    public function __construct() {

        parent::__construct(
            new Vipps_Payment_Gateway_Request_BuilderComposite_VippsRefundRequest(),
            new Vipps_Payment_Gateway_Http_TransferFactory('POST', '/ecomm/v2/payments/:orderId/refund', ['orderId' => 'orderId']),
            new Vipps_Payment_Gateway_Http_Client_Curl(),
            new Vipps_Payment_Gateway_Response_TransactionHandler(),
            new Vipps_Payment_Gateway_Validator_Composite_VippsRefundValidator()
        );

        $this->paymentDetailsProvider = new Vipps_Payment_Gateway_Command_PaymentDetailsProvider();
        $this->transactionBuilder = new Vipps_Payment_Gateway_Transaction_TransactionBuilder();
        $this->orderRepository = Mage::getSingleton('vipps_payment/orderRepository');
    }

    /**
     * {@inheritdoc}
     *
     * @param array $commandSubject
     *
     * @return Vipps_Payment_Gateway_Validator_Result|array|bool|null
     * @throws Mage_Core_Exception
     */
    public function execute(array $commandSubject)
    {
        $amount = $this->subjectReader->readAmount($commandSubject);
        $amount = (int)round($this->formatPrice($amount) * 100);

        $response = $this->paymentDetailsProvider->get($commandSubject);
        $transaction = $this->transactionBuilder->setData($response)->build();

        // try to refund based on payment details data
        if ($this->refundBasedOnPaymentDetails($commandSubject, $transaction)) {
            return true;
        }

        // try to refund based on refund service itself
        if ($transaction->getTransactionSummary()->getRemainingAmountToRefund() < $amount) {
            \Mage::throwException(__('Refund amount is higher then remaining amount to refund'));
        }

        $requestId = $this->getLastFailedRequestId($transaction, $amount);
        if ($requestId) {
            $commandSubject['requestId'] = $requestId;
        }

        return parent::execute($commandSubject);
    }

    /**
     * Try to refund based on GetPaymentDetails service.
     *
     * @param $commandSubject
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return bool
     * @throws \Mage_Core_Exception
     */
    private function refundBasedOnPaymentDetails($commandSubject, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        $payment = $this->subjectReader->readPayment($commandSubject);
        $amount = $this->subjectReader->readAmount($commandSubject);
        $amount = (int)round($this->formatPrice($amount) * 100);

        $orderAdapter = $payment->getOrder();
        $orderIncrementId = $orderAdapter->getOrderIncrementId();

        $order = $this->orderRepository->get($orderAdapter->getId());

        $magentoTotalRefunded = (int)round($this->formatPrice($order->getTotalRefunded()) * 100);
        $vippsTotalRefunded = $transaction->getTransactionSummary()->getRefundedAmount();

        $deltaTotalRefunded = $vippsTotalRefunded - $magentoTotalRefunded;
        if ($deltaTotalRefunded > 0) {
            // In means that in Vipps the refunded amount is higher then in Magento
            // It can happened if previous operation was successful in vipps
            // but for some reason Magento didn't get response

            // Check that we are trying to refund the same amount as has been already refunded in Vipps
            // otherwise - show an error about desync
            if ((int)$amount === (int)$deltaTotalRefunded) {
                //prepare refund response based on data from getPaymentDetails service
                $responseBody = $this->prepareResponseBody($transaction, $amount, $orderIncrementId);
                if (!is_array($responseBody)) {
                    Mage::throwException(__('An error occurred during refund info sync.'));
                }
                if ($this->handler) {
                    $this->handler->handle($commandSubject, $responseBody);
                }
                return true;
            } else {
                $suggestedAmountToRefund = $this->formatPrice($deltaTotalRefunded / 100);
                $message = __(
                    'Refunded amount is not the same as you are trying to refund.'
                    . PHP_EOL . ' Payment information was not synced correctly between Magento and Vipps.'
                    . PHP_EOL . ' It might be happened that previous operation was successfully completed in Vipps'
                    . PHP_EOL . ' but Magento did not receive a response.'
                    . PHP_EOL . ' To be in sync you have to refund the same amount that has been already refunded'
                    . PHP_EOL . ' in Vipps: %s',
                    $suggestedAmountToRefund
                );

                Mage::throwException($message);
            }
        }

        return false;
    }

    /**
     * Prepare response body based of GetPaymentDetails service data.
     *
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     * @param $amount
     * @param $orderId
     *
     * @return array|null
     */
    private function prepareResponseBody(Vipps_Payment_Gateway_Transaction_Transaction $transaction, $amount, $orderId)
    {
        $item = $this->findLatestSuccessHistoryItem($transaction, $amount);
        if ($item) {
            return [
                'orderId'            => $orderId,
                'transaction'        => [
                    'amount'          => $item->getAmount(),
                    'status'          => Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_STATUS_REFUND,
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
        return null;
    }

    /**
     * Get latest successful transaction log history item.
     *
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     * @param $amount
     *
     * @return TransactionLogHistoryItem|null
     */
    private function findLatestSuccessHistoryItem(Vipps_Payment_Gateway_Transaction_Transaction $transaction, $amount)
    {
        foreach ($transaction->getTransactionLogHistory()->getItems() as $item) {
            if ($item->getOperation() == Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_OPERATION_REFUND
                && $item->isOperationSuccess()
                && $item->getAmount() == $amount
            ) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Retrieve request id of last failed operation from transaction log history.
     *
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     * @param int $amount
     *
     * @return string|null
     */
    private function getLastFailedRequestId(Vipps_Payment_Gateway_Transaction_Transaction $transaction, $amount)
    {
        foreach ($transaction->getTransactionLogHistory()->getItems() as $item) {
            if ($item->getOperation() != Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_OPERATION_REFUND) {
                continue;
            }
            if (true !== $item->isOperationSuccess() && $item->getAmount() == $amount) {
                return $item->getRequestId();
            }
        }
        return null;
    }
}
