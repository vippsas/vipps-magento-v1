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
 * Class Vipps_Payment_Model_Cron_FetchOrderFromVipps
 */
class Vipps_Payment_Model_Cron_FetchOrderFromVipps extends Vipps_Payment_Model_Cron_AbstractCron
{
    /**
     * Order collection page size
     */
    const COLLECTION_PAGE_SIZE = 100;

    /**
     * @var Vipps_Payment_Gateway_Transaction_TransactionBuilder
     */
    private $transactionBuilder;

    /**
     * @var Vipps_Payment_Model_OrderPlace
     */
    private $orderPlace;

    /**
     * @var Vipps_Payment_Model_Quote_AttemptManagement
     */
    private $attemptManagement;

    /**
     * @var Vipps_Payment_Model_Adapter_CartRepository
     */
    private $quoteRepository;

    /**
     * @var Vipps_Payment_Model_OrderRepository
     */
    private $orderLocator;

    /**
     * @var Vipps_Payment_Model_QuoteRepository
     */
    private $vippsQuoteRepository;

    /**
     * FetchOrderFromVipps constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->transactionBuilder = new Vipps_Payment_Gateway_Transaction_TransactionBuilder();
        $this->orderPlace = Mage::getSingleton('vipps_payment/orderPlace');
        $this->attemptManagement = Mage::getSingleton('vipps_payment/quote_attemptManagement');
        $this->quoteRepository = Mage::getSingleton('vipps_payment/adapter_cartRepository');
        $this->vippsQuoteRepository = Mage::getSingleton('vipps_payment/quoteRepository');
        $this->orderLocator = Mage::getSingleton('vipps_payment/orderRepository');
    }

    /**
     * Create orders from Vipps that are not created in Magento yet
     *
     */
    public function execute()
    {
        try {
            $currentPage = 1;
            do {
                $vippsQuoteCollection = $this->createCollection($currentPage);
                $this->logger->debug('Fetched payment details');
                /** @var Vipps_Payment_Model_Quote $vippsQuote */
                foreach ($vippsQuoteCollection as $vippsQuote) {
                    $this->processQuote($vippsQuote);
                    usleep(1000000); //delay for 1 second
                }
                $currentPage++;
            } while ($currentPage <= $vippsQuoteCollection->getLastPageNumber());
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
    }

    /**
     * @param $currentPage
     *
     * @return Vipps_Payment_Model_Resource_Quote_Collection
     */
    private function createCollection($currentPage)
    {
        /** @var Vipps_Payment_Model_Resource_Quote_Collection $collection */
        $collection = Mage::getModel('vipps_payment/quote')->getCollection();

        $collection
            ->setPageSize(self::COLLECTION_PAGE_SIZE)
            ->setCurPage($currentPage)
            ->addFieldToFilter(
                'attempts',
                [
                    ['lt' => $this->cancellationConfig->getAttemptsMaxCount()],
                    ['null' => 1]
                ]
            )
            ->addFieldToFilter(
                Vipps_Payment_Model_QuoteStatusInterface::FIELD_STATUS,
                ['in' => [Vipps_Payment_Model_QuoteStatusInterface::STATUS_NEW, Vipps_Payment_Model_QuoteStatusInterface::STATUS_PROCESSING]]
            ); // Filter new and place failed quotes.

        return $collection;
    }

    /**
     * @param Vipps_Payment_Model_Quote $vippsQuote
     * @throws Mage_Core_Exception
     */
    private function processQuote(Vipps_Payment_Model_Quote $vippsQuote)
    {
        $vippsQuoteStatus = Vipps_Payment_Model_QuoteStatusInterface::STATUS_PROCESSING;
        $attemptMessage = __('Waiting while customer accept payment');

        try {
            $environmentInfo = $this->storeEmulation->startEnvironmentEmulation($vippsQuote->getStoreId());
            // Register new attempt.
            $attempt = $this->attemptManagement->createAttempt($vippsQuote);

            // Get Magento Quote for processing.
            $transaction = $this->getTransaction($vippsQuote->getReservedOrderId());
            if ($transaction->transactionWasCancelled() || $transaction->transactionWasVoided()) {
                $attemptMessage = __('Transaction was cancelled in Vipps');
                $vippsQuoteStatus = Vipps_Payment_Model_QuoteStatusInterface::STATUS_CANCELED;
            } elseif ($transaction->isTransactionReserved()) {
                $order = $this->placeOrder($vippsQuote, $transaction);
                if ($order) {
                    $vippsQuoteStatus = Vipps_Payment_Model_QuoteStatusInterface::STATUS_PLACED;
                    $attemptMessage = __('Placed');
                }
            } elseif ($transaction->isTransactionExpired()) {
                $vippsQuoteStatus = Vipps_Payment_Model_QuoteStatusInterface::STATUS_EXPIRED;
                $attemptMessage = __('Transaction has been expired');
            }
        } catch (\Exception $e) {
            $vippsQuoteStatus = $this->isMaxAttemptsReached($vippsQuote)
                ? Vipps_Payment_Model_QuoteStatusInterface::STATUS_PLACE_FAILED
                : Vipps_Payment_Model_QuoteStatusInterface::STATUS_PROCESSING;
            $this->logger->critical($e->getMessage(), ['vipps_quote_id' => $vippsQuote->getId()]);
            $attemptMessage = $e->getMessage();
        } finally {
            if(isset($environmentInfo)) {
                $this->storeEmulation->stopEnvironmentEmulation($environmentInfo);
            }
            $vippsQuote->setStatus($vippsQuoteStatus);
            $this->vippsQuoteRepository->save($vippsQuote);

            if (isset($attempt)) {
                $attempt->setMessage($attemptMessage);
                $this->attemptManagement->save($attempt);
            }
        }
    }

    /**
     * @param $orderId
     *
     * @return Vipps_Payment_Gateway_Transaction_Transaction
     * @throws Vipps_Payment_Gateway_Command_CommandException
     */
    private function getTransaction($orderId)
    {
        $response = $this->commandManager->getPaymentDetails(
            ['orderId' => $orderId]
        );
        return $this->transactionBuilder->setData($response)->build();
    }

    /**
     * @param Vipps_Payment_Model_Quote $vippsQuote
     * @param Vipps_Payment_Gateway_Transaction_Transaction $transaction
     *
     * @return Mage_Sales_Model_Order|null
     * @throws Mage_Core_Exception
     * @throws Vipps_Payment_Gateway_Exception_VippsException
     * @throws Vipps_Payment_Gateway_Exception_WrongAmountException
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    private function placeOrder(Vipps_Payment_Model_Quote $vippsQuote, Vipps_Payment_Gateway_Transaction_Transaction $transaction)
    {
        $quote = $this->quoteRepository->get($vippsQuote->getQuoteId());

        $existentOrder = $this->orderLocator->getByIncrement($vippsQuote->getReservedOrderId());

        $order = isset($existentOrder) ? $existentOrder : $this->orderPlace->execute($quote, $transaction);
        if ($order) {
            $this->logger->debug(sprintf('Order placed: "%s"', $order->getIncrementId()));
        } else {
            $this->logger->critical(sprintf(
                'Order has not been placed, quote id: "%s", reserved_order_id: "%s"',
                $quote->getId(),
                $quote->getReservedOrderId()
            ));
        }
        return $order;
    }

    /**
     * Validate Vipps Quote expiration.
     *
     * @param Vipps_Payment_Model_Quote $vippsQuote
     * @return bool
     * @throws \Exception
     */
    private function isQuoteExpired(Vipps_Payment_Model_Quote $vippsQuote)
    {
        $createdAt = new DateTime($vippsQuote->getCreatedAt());

        $interval = new \DateInterval("PT{$this->cancellationConfig->getInactivityTime()}M");  //@codingStandardsIgnoreLine

        $createdAt->add($interval);

        return !$createdAt->diff(new DateTime())->invert;
    }

    /**
     * Check for attempts count.
     *
     * @param Vipps_Payment_Model_Quote $vippsQuote
     * @return bool
     */
    private function isMaxAttemptsReached(Vipps_Payment_Model_Quote $vippsQuote)
    {
        return $vippsQuote->getAttempts() >= $this->cancellationConfig->getAttemptsMaxCount();
    }
}
