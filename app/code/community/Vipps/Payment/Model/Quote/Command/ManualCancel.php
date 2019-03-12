<?php

use Vipps\Payment\Model\Adapter\CancelFacade;
use Vipps\Payment\Model\Adapter\Order\Cancellation\Config;

/**
 * Restart Vipps Quote processing.
 */
class Vipps_Payment_Model_Quote_Command_ManualCancel
{
    /**
     * @var Vipps_Payment_Model_Quote
     */
    private $vippsQuote;

    /**
     * @var Vipps_Payment_Model_Adapter_CartRepository
     */
    private $cartRepository;

    /**
     * @var Vipps_Payment_Model_Quote_CancelFacade
     */
    private $cancelFacade;

    /**
     * Restart constructor.
     * @param Vipps_Payment_Model_Quote $vippsQuote
     * @throws Mage_Core_Exception
     */
    public function __construct(
        Vipps_Payment_Model_Quote $vippsQuote
    ) {
        $this->vippsQuote = $vippsQuote;
        $this->cartRepository = Mage::getSingleton('vipps_payment/adapter_cartRepository');
        $this->cancelFacade = Mage::getSingleton('vipps_payment/quote_cancelFacade');
    }

    /**
     * Verify is Quote Processing allowed for restart.
     *
     * @return bool
     */
    public function isAllowed()
    {
        return in_array(
            $this->vippsQuote->getStatus(),
            [
                Vipps_Payment_Model_QuoteStatusInterface::STATUS_PLACE_FAILED,
                Vipps_Payment_Model_QuoteStatusInterface::STATUS_CANCEL_FAILED
            ],
            true
        );
    }

    /**
     * @throws Mage_Core_Exception
     */
    public function execute()
    {
        try {
            $quote = $this->cartRepository->get($this->vippsQuote->getQuoteId());

            $this
                ->cancelFacade
                ->cancel($this->vippsQuote, $quote);
        } catch (\Exception $exception) {
            Mage::throwException(__('Failed to cancel the order. Please contact support team.'));
        }
    }
}
