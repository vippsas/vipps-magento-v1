<?php

/**
 * Class RestartFactory
 */
class Vipps_Payment_Model_Quote_Command_RestartFactory
{
    /**
     * @param Vipps_Payment_Model_Quote $vippsQuote
     * @return false|Mage_Core_Model_Abstract
     */
    public function create(Vipps_Payment_Model_Quote $vippsQuote)
    {
        return Mage::getModel('vipps_payment/quote_command_restart', $vippsQuote);
    }
}
