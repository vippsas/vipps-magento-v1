<?php
/**
 * Copyright 2018 Vipps
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 *  documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 *  the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 *  and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 *  TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 *  THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 *  CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 *  IN THE SOFTWARE.
 *
 */

/**
 * Quote Cancellation Facade.
 * It cancels the quote. Provides an ability to send cancellation request to Vipps.
 */
class Vipps_Payment_Model_Quote_CancelFacade
{
    /**
     * @var Vipps_Payment_Gateway_Command_CommandManager
     */
    private $commandManager;

    /**
     * @var Vipps_Payment_Model_QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var Vipps_Payment_Model_Quote_AttemptManagement
     */
    private $attemptManagement;

    /**
     * CancellationFacade constructor.
     * @throws Mage_Core_Exception
     */
    public function __construct()
    {
        $this->commandManager = Mage::helper('vipps_payment/gateway')->getSingleton('command_commandManager');
        $this->quoteRepository = Mage::getSingleton('vipps_payment/quoteRepository');
        $this->attemptManagement = Mage::getModel('vipps_payment/quote_attemptManagement');
    }

    /**
     * vipps_monitoring extension attribute requires to be loaded in the quote.
     *
     * @param Vipps_Payment_Model_Quote $vippsQuote
     * @param Mage_Sales_Model_Quote $quote
     * @throws Mage_Core_Exception
     */
    public function cancel(
        Vipps_Payment_Model_Quote $vippsQuote,
        Mage_Sales_Model_Quote $quote
    ) {
        try {
            $attempt = $this->attemptManagement->createAttempt($vippsQuote);
            // cancel order on vipps side
            $this->commandManager->cancel($quote->getPayment());
            $vippsQuote->setStatus(Vipps_Payment_Model_QuoteStatusInterface::STATUS_CANCELED);
            $attempt->setMessage('The order has been canceled.');
        } catch (\Exception $exception) {
            // Log the exception
            $vippsQuote->setStatus(Vipps_Payment_Model_QuoteStatusInterface::STATUS_CANCEL_FAILED);
            $attempt->setMessage($exception->getMessage());
            throw $exception;
        } finally {
            if (isset($attempt)) {
                $this->attemptManagement->save($attempt);
            }
            $this->quoteRepository->save($vippsQuote);
        }
    }
}
