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
 * Class ClearQuotesHistory
 * @package Vipps\Payment\Cron
 */
class Vipps_Payment_Model_Cron_ClearQuotesHistory extends Vipps_Payment_Model_Cron_AbstractCron
{
    /**
     * Clear old vipps quote history.
     */
    public function execute()
    {
        $days = $this->cancellationConfig->getQuoteStoragePeriod();

        if (!$days) {
            $this->logger->debug('No days interval installed to remove quotes information');
            return;
        }

        try {
            $dateRemoveTo = new DateTime();
            $dateRemoveTo->sub(new \DateInterval("P{$days}D"));  //@codingStandardsIgnoreLine
            $dateTimeFormatted = $dateRemoveTo->format(Varien_Db_Adapter_Interface::ISO_DATETIME_FORMAT);

            $this->logger->debug('Remove quotes information till ' . $dateTimeFormatted);

            /** @var Vipps_Payment_Model_Resource_Quote_Collection $collection */
            $collection = Mage::getModel('vipps_payment/quote')->getCollection();

            $collection->addFieldToFilter('updated_at', ['lt' => $dateTimeFormatted]);

            $query = $collection
                ->getSelect()
                ->deleteFromSelect('main_table');

            $collection->getConnection()->query($query);  //@codingStandardsIgnoreLine

            $this->logger->debug('Deleted records: ' . $query);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
