<?php

/**
 * Restart Vipps Quote processing.
 */
class Vipps_Payment_Model_Quote_Command_Restart
{
    /**
     * @var \Vipps_Payment_Model_Quote
     */
    private $vippsQuote;

    /**
     * Restart constructor.
     * @param Vipps_Payment_Model_Quote $vippsQuote
     */
    public function __construct(Vipps_Payment_Model_Quote $vippsQuote)
    {
        $this->vippsQuote = $vippsQuote;
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
            [Vipps_Payment_Model_QuoteStatusInterface::STATUS_PLACE_FAILED, Vipps_Payment_Model_QuoteStatusInterface::STATUS_EXPIRED],
            true
        );
    }

    /**
     * Mark Vipps Quote as ready for restart.
     *
     * @throws Exception
     */
    public function execute()
    {
        $this
            ->vippsQuote
            ->clearAttempts()
            ->setStatus(\Vipps_Payment_Model_QuoteStatusInterface::STATUS_PROCESSING);

        $this->vippsQuote->save();
    }
}
