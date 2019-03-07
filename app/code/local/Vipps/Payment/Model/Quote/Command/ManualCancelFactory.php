<?php

/**
 * Class RestartFactory
 */
class Vipps_Payment_Model_Quote_Command_ManualCancelFactory
{
    /**
     * @param Vipps_Payment_Model_Quote $vippsQuote
     * @return Vipps_Payment_Model_Quote_Command_ManualCancel
     */
    public function create(Vipps_Payment_Model_Quote $vippsQuote)
    {
        return Mage::getModel('vipps_payment/quote_command_manualCancel', $vippsQuote);
    }
}
