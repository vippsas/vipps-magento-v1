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
 * Class FetchOrderStatus
 * @package Vipps\Payment\Cron
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vipps_Payment_Model_Cron_CancelQuoteByAttempts extends Vipps_Payment_Model_Cron_AbstractCron
{
    /**
     * @var Vipps_Payment_Model_Quote_CancelFacade
     */
    private $cancellationFacade;

    /**
     * @var Vipps_Payment_Model_Adapter_CartRepository
     */
    private $cartRepository;

    /**
     * @var Vipps_Payment_Model_Quote_AttemptManagement
     */
    private $attemptManagement;

    /**
     * FetchOrderFromVipps constructor.
     *
     * @throws Mage_Core_Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->cancellationFacade = Mage::getSingleton('vipps_payment/quote_cancelFacade');
        $this->cartRepository = Mage::getSingleton('vipps_payment/adapter_cartRepository');
        $this->attemptManagement = Mage::getSingleton('vipps_payment/quote_attemptManagement');
    }

    /**
     * Create orders from Vipps that are not created in Magento yet
     */
    public function execute()
    {
        try {
            $currentPage = 1;
            do {
                $quoteCollection = $this->createCollection($currentPage);
                $this->logger->debug('Fetched quote collection to cancel');
                foreach ($quoteCollection as $quote) {
                    $this->processQuote($quote);
                    usleep(1000000); //delay for 1 second
                }
                $currentPage++;
            } while ($currentPage <= $quoteCollection->getLastPageNumber());
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
    }

    /**
     * Get vipps quote collection to cancel.
     * Conditions are:
     * number of attempts greater than allowed
     *
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
                ['gteq' => $this->cancellationConfig->getAttemptsMaxCount()]
            );

        // Filter processing cancelled quotes.
        $collection->addFieldToFilter(
            Vipps_Payment_Model_QuoteStatusInterface::FIELD_STATUS,
            ['in' => [
                Vipps_Payment_Model_QuoteStatusInterface::STATUS_NEW,
                Vipps_Payment_Model_QuoteStatusInterface::STATUS_PLACE_FAILED,
                Vipps_Payment_Model_QuoteStatusInterface::STATUS_PROCESSING
            ]]
        );

        return $collection;
    }

    /**
     * Main process
     *
     * @param Vipps_Payment_Model_Quote $vippsQuote
     *
     * @throws Exception
     */
    private function processQuote(Vipps_Payment_Model_Quote $vippsQuote)
    {
        $this->logger->info('Start quote cancelling', ['vipps_quote_id' => $vippsQuote->getId()]);

        try {
            $environmentInfo = $this->storeEmulation->startEnvironmentEmulation($vippsQuote->getStoreId());

            if ($this->cancellationConfig->isAutomatic($vippsQuote->getStoreId())) {
                $quote = $this->cartRepository->get($vippsQuote->getQuoteId());

                $attempt = $this->attemptManagement->createAttempt($vippsQuote, true);

                $attempt
                    ->setMessage(__(
                        'Max number of attempts reached (%s)',
                        $this->cancellationConfig->getAttemptsMaxCount()
                    ));

                $this
                    ->cancellationFacade
                    ->cancel($vippsQuote, $quote);

                $attempt->save();
            }
        } catch (\Exception $e) {
            if (isset($attempt)) {
                $attempt
                    ->setMessage($e->getMessage())
                    ->save();
            }
            $this->logger->critical($e->getMessage(), ['quote_id' => $vippsQuote->getId()]);
        } finally {
            if (isset($environmentInfo)) {
                $this->storeEmulation->stopEnvironmentEmulation($environmentInfo);
            }
        }
    }
}
