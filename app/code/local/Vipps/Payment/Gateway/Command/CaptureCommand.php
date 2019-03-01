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
 * Class CaptureCommand
 */
class Vipps_Payment_Gateway_Command_CaptureCommand extends Vipps_Payment_Gateway_Command_GatewayCommand
{
    use Vipps_Payment_Model_Helper_Formatter;
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

    public function __construct()
    {
        parent::__construct(
            new Vipps_Payment_Gateway_Request_BuilderComposite_VippsCaptureRequest(),
            new Vipps_Payment_Gateway_Http_TransferFactory('POST', '/ecomm/v2/payments/:orderId/capture', ['orderId' => 'orderId']),
            new Vipps_Payment_Gateway_Http_Client_Curl(),
            new Vipps_Payment_Gateway_Response_TransactionHandler(),
            new Vipps_Payment_Gateway_Validator_Composite_VippsCaptureValidator()
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
     * @return Result|array|bool|null
     * @throws ClientException
     * @throws ConverterException
     * @throws LocalizedException
     * @throws Vipps_Payment_Gateway_Exception_VippsException
     */
    public function execute(array $commandSubject)
    {
        $amount = $this->subjectReader->readAmount($commandSubject);
        $amount = (int)($this->formatPrice($amount) * 100);

        $response = $this->paymentDetailsProvider->get($commandSubject);
        $transaction = $this->transactionBuilder->setData($response)->build();

        // try to capture based on payment details data
        if ($this->captureBasedOnPaymentDetails($commandSubject, $transaction)) {
            return true;
        }

        // try to capture based on capture service itself
        if ($transaction->getTransactionSummary()->getRemainingAmountToCapture() < $amount) {
            \Mage::throwException(__('Captured amount is higher then remaining amount to capture'));
        }

        $requestId = $this->getLastFailedRequestId($transaction, $amount);
        if ($requestId) {
            $commandSubject['requestId'] = $requestId;
        }

        return parent::execute($commandSubject);
    }

    /**
     * Try to capture based on GetPaymentDetails service.
     *
     * @param $commandSubject
     * @param \Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    private function captureBasedOnPaymentDetails($commandSubject, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        $payment = $this->subjectReader->readPayment($commandSubject);
        $amount = $this->subjectReader->readAmount($commandSubject);
        $amount = (int)($this->formatPrice($amount) * 100);

        $order = $payment->getOrder();
        $orderIncrementId = $order->getOrderIncrementId();

        $order = $this->orderRepository->get($order->getId());

        $magentoTotalDue = (int)($this->formatPrice($order->getTotalDue()) * 100);
        $vippsTotalDue = $transaction->getTransactionSummary()->getRemainingAmountToCapture();

        $deltaTotalDue = $magentoTotalDue - $vippsTotalDue;
        if ($deltaTotalDue > 0) {
            // In means that in Vipps the remainingAmountToCapture is less then in Magento
            // It can happened if previous operation was successful in vipps
            // but for some reason Magento didn't get response

            // Check that we are trying to capture the same amount as has been already captured in Vipps
            // otherwise - show an error about desync
            if ((int)$amount === (int)$deltaTotalDue) {
                //prepare capture response based on data from getPaymentDetails service
                $responseBody = $this->prepareResponseBody($transaction, $amount, $orderIncrementId);
                if (!is_array($responseBody)) {
                    Mage::throwException(__('An error occurred during capture info sync.'));
                }
                if ($this->handler) {
                    $this->handler->handle($commandSubject, $responseBody);
                }
                return true;
            } else {
                $suggestedAmountToCapture = $this->formatPrice($deltaTotalDue / 100);
                $message = __(
                    'Captured amount is not the same as you are trying to capture.'
                    . PHP_EOL . ' Payment information was not synced correctly between Magento and Vipps.'
                    . PHP_EOL . ' It might be happened that previous operation was successfully completed in Vipps'
                    . PHP_EOL . ' but Magento did not receive a response.'
                    . PHP_EOL . ' To be in sync you have to capture the same amount that has been already captured'
                    . PHP_EOL . ' in Vipps: %s',
                    $suggestedAmountToCapture
                );

                Mage::throwException($message);
            }
        }

        return false;
    }

    /**
     * Prepare response body based of GetPaymentDetails service data.
     *
     * @param \Vipps_Payment_Gateway_Transaction_Transaction $transaction
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
                'transactionInfo'    => [
                    'amount'          => $item->getAmount(),
                    'status'          => Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_STATUS_CAPTURED,
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
     * @param \Vipps_Payment_Gateway_Transaction_Transaction $transaction
     * @param $amount
     *
     * @return Vipps_Payment_Gateway_Transaction_TransactionLogHistory_Item|null
     */
    private function findLatestSuccessHistoryItem(Vipps_Payment_Gateway_Transaction_Transaction $transaction, $amount)
    {
        foreach ($transaction->getTransactionLogHistory()->getItems() as $item) {
            if ($item->getOperation() == Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_OPERATION_CAPTURE
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
     * @param \Vipps_Payment_Gateway_Transaction_Transaction $transaction
     * @param int $amount
     *
     * @return string|null
     */
    private function getLastFailedRequestId(Vipps_Payment_Gateway_Transaction_Transaction $transaction, $amount)
    {
        foreach ($transaction->getTransactionLogHistory()->getItems() as $item) {
            if ($item->getOperation() != Vipps_Payment_Gateway_Transaction_Transaction::TRANSACTION_OPERATION_CAPTURE) {
                continue;
            }
            if (true !== $item->isOperationSuccess() && $item->getAmount() == $amount) {
                return $item->getRequestId();
            }
        }
        return null;
    }
}
