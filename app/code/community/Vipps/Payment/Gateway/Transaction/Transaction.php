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
 * Class Transaction
 * @package Vipps\Payment\Gateway\Transaction
 */
class Vipps_Payment_Gateway_Transaction_Transaction
{
    /**
     * @var string
     */
    const TRANSACTION_STATUS_INITIATE = 'initiate';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_INITIATED = 'initiated';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_REGISTER = 'register';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_RESERVE = 'reserve';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_RESERVED = 'reserved';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_SALE = 'sale';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_CANCEL = 'cancel';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_CANCELLED = 'cancelled';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_VOID = 'void';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_AUTOREVERSAL = 'autoreversal';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_AUTOCANCEL = 'autocancel';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_FAILED = 'failed';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_REJECTED = 'rejected';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_CAPTURED = 'captured';

    /**
     * @var string
     */
    const TRANSACTION_STATUS_REFUND = 'refund';

    /**
     * @var string
     */
    const TRANSACTION_OPERATION_INITIATE = 'initiate';

    /**
     * @var string
     */
    const TRANSACTION_OPERATION_RESERVE = 'reserve';

    /**
     * @var string
     */
    const TRANSACTION_OPERATION_CAPTURE = 'capture';

    /**
     * @var string
     */
    const TRANSACTION_OPERATION_REFUND = 'refund';

    /**
     * @var string
     */
    const TRANSACTION_OPERATION_CANCEL = 'cancel';

    /**
     * @var string
     */
    const TRANSACTION_OPERATION_VOID = 'void';

    /**
     * @var Vipps_Payment_Gateway_Transaction_TransactionInfo
     */
    private $transactionInfo;

    /**
     * @var Vipps_Payment_Gateway_Transaction_TransactionSummary
     */
    private $transactionSummary;

    /**
     * @var \Vipps_Payment_Gateway_Transaction_TransactionLogHistory
     */
    private $transactionLogHistory;

    /**
     * @var Vipps_Payment_Gateway_Transaction_UserDetails
     */
    private $userDetails;

    /**
     * @var \Vipps_Payment_Gateway_Transaction_ShippingDetails
     */
    private $shippingDetails;

    /**
     * @var string
     */
    private $orderId;

    /**
     * Transaction constructor.
     *
     * @param Vipps_Payment_Gateway_Transaction_TransactionInfo $transactionInfo
     * @param Vipps_Payment_Gateway_Transaction_TransactionSummary $transactionSummary
     * @param Vipps_Payment_Gateway_Transaction_TransactionLogHistory $transactionLogHistory
     * @param \Vipps_Payment_Gateway_Transaction_UserDetails|null $userDetails
     * @param \Vipps_Payment_Gateway_Transaction_ShippingDetails|null $shippingDetails
     */
    public function __construct(
        $orderId,
        Vipps_Payment_Gateway_Transaction_TransactionInfo $transactionInfo,
        Vipps_Payment_Gateway_Transaction_TransactionSummary $transactionSummary,
        Vipps_Payment_Gateway_Transaction_TransactionLogHistory $transactionLogHistory,
        \Vipps_Payment_Gateway_Transaction_UserDetails $userDetails = null,
        \Vipps_Payment_Gateway_Transaction_ShippingDetails $shippingDetails = null
    ) {
        $this->orderId = $orderId;
        $this->transactionInfo = $transactionInfo;
        $this->transactionSummary = $transactionSummary;
        $this->transactionLogHistory = $transactionLogHistory;
        $this->userDetails = $userDetails;
        $this->shippingDetails = $shippingDetails;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @return Vipps_Payment_Gateway_Transaction_TransactionSummary
     */
    public function getTransactionSummary()
    {
        return $this->transactionSummary;
    }

    /**
     * @return null|\Vipps_Payment_Gateway_Transaction_UserDetails
     */
    public function getUserDetails()
    {
        return $this->userDetails;
    }

    /**
     * @return null|ShippingDetails
     */
    public function getShippingDetails()
    {
        return $this->shippingDetails;
    }

    /**
     * @return bool
     */
    public function isExpressCheckout()
    {
        return $this->userDetails === null ? false : true;
    }

    /**
     * Is initiate transaction.
     *
     * @return bool
     */
    public function isInitiate()
    {
        return $this->getTransactionInfo()->getStatus() === self::TRANSACTION_STATUS_INITIATE;
    }

    /**
     * @return Vipps_Payment_Gateway_Transaction_TransactionInfo
     */
    public function getTransactionInfo()
    {
        return $this->transactionInfo;
    }

    /**
     * @return bool
     */
    public function isTransactionAborted()
    {
        $abortedStatuses = [
            self::TRANSACTION_STATUS_CANCEL,
            self::TRANSACTION_STATUS_CANCELLED,
            self::TRANSACTION_STATUS_AUTOCANCEL,
            self::TRANSACTION_STATUS_REJECTED,
            self::TRANSACTION_STATUS_FAILED,
            self::TRANSACTION_STATUS_VOID
        ];

        return in_array($this->getTransactionInfo()->getStatus(), $abortedStatuses);
    }

    /**
     * Check that transaction has not been reserved yet
     *
     * @return bool
     */
    public function isTransactionReserved()
    {
        $statuses = [
            self::TRANSACTION_STATUS_RESERVE,
            self::TRANSACTION_STATUS_RESERVED
        ];
        $item = $this->transactionLogHistory->getLastSuccessItem();
        if (in_array($item->getOperation(), $statuses)) {
            return true;
        }

        return false;
    }

    /**
     * Check that transaction has not been reserved yet
     *
     * @return bool
     */
    public function isTransactionCaptured()
    {
        $statuses = [
            self::TRANSACTION_OPERATION_CAPTURE,
            self::TRANSACTION_STATUS_CAPTURED
        ];
        $item = $this->transactionLogHistory->getLastSuccessItem();
        if (in_array($item->getOperation(), $statuses)) {
            return true;
        }

        return false;
    }
    
    /**
     * @return string|null
     */
    public function getTransactionStatus()
    {
        if ($this->transactionWasCancelled() || $this->transactionWasVoided()) {
            return self::TRANSACTION_STATUS_CANCELLED;
        }

        if ($this->transactionWasReserved()) {
            return self::TRANSACTION_STATUS_RESERVED;
        }

        if ($this->transactionWasInitiated()) {
            return self::TRANSACTION_STATUS_INITIATED;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isTransactionInitiated()
    {
        $item = $this->getTransactionLogHistory()->getLastSuccessItem();
        if ($item && $item->getOperation() == self::TRANSACTION_OPERATION_INITIATE) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isTransactionExpired()
    {
        $item = $this->getTransactionLogHistory()->getLastSuccessItem();

        $now = new \DateTime(); //@codingStandardsIgnoreLine
        $createdAt = new \DateTimeImmutable($item->getTimeStamp()); //@codingStandardsIgnoreLine

        $interval = new \DateInterval("PT5M");  //@codingStandardsIgnoreLine
        $expiredAt = $createdAt->add($interval);

        return $expiredAt < $now;
    }

    /**
     * Check that transaction has been initiated
     *
     * @return bool
     */
    public function transactionWasInitiated()
    {
        $item = $this->transactionLogHistory
            ->findSuccessItemWithOperation(self::TRANSACTION_OPERATION_INITIATE);
        if ($item) {
            return true;
        }

        return false;
    }

    /**
     * Check that transaction has been cancelled
     *
     * @return bool
     */
    public function transactionWasCancelled()
    {
        $item = $this->transactionLogHistory
            ->findSuccessItemWithOperation(self::TRANSACTION_OPERATION_CANCEL);
        if ($item) {
            return true;
        }

        return false;
    }

    /**
     * Check that transaction has been cancelled
     *
     * @return bool
     */
    public function transactionWasVoided()
    {
        $item = $this->transactionLogHistory
            ->findSuccessItemWithOperation(self::TRANSACTION_OPERATION_VOID);
        if ($item) {
            return true;
        }

        return false;
    }

    /**
     * Check that transaction has been reserved
     *
     * @return bool
     */
    public function transactionWasReserved()
    {
        $item = $this->getTransactionLogHistory()
            ->findSuccessItemWithOperation(self::TRANSACTION_OPERATION_RESERVE);
        if ($item) {
            return true;
        }

        return false;
    }

    /**
     * Method to retrieve Transaction Id.
     *
     * @return null|string
     */
    public function getTransactionId()
    {
        return $this->getTransactionInfo()->getTransactionId() ?:
            $this->getTransactionLogHistory()->getLastTransactionId();
    }

    /**
     * @return Vipps_Payment_Gateway_Transaction_TransactionLogHistory
     */
    public function getTransactionLogHistory()
    {
        return $this->transactionLogHistory;
    }
}
