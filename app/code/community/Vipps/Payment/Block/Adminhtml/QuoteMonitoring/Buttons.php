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

class Vipps_Payment_Block_Adminhtml_QuoteMonitoring_Buttons extends Mage_Adminhtml_Block_Template
{
    /**
     * @var Vipps_Payment_Model_Quote_Command_RestartFactory
     */
    private $restartFactory;

    /**
     * @var Vipps_Payment_Model_Quote_Command_ManualCancelFactory
     */
    private $cancelFactory;

    public function __construct(array $args = array())
    {
        parent::__construct($args);

        $this->restartFactory = Mage::getSingleton('vipps_payment/quote_command_restartFactory');
        $this->cancelFactory = Mage::getSingleton('vipps_payment/quote_command_manualCancelFactory');
    }

    /**
     * @return bool
     */
    public function isRestartVisible()
    {
        $restart = $this->restartFactory->create($this->getVippsQuote());

        return $restart->isAllowed();
    }

    /**
     * @return Vipps_Payment_Model_Quote
     */
    public function getVippsQuote()
    {
        return Mage::registry('vipps_quote');
    }

    /**
     * @return bool
     */
    public function isCancelVisible()
    {
        $cancel = $this->cancelFactory->create($this->getVippsQuote());

        return $cancel->isAllowed();
    }
}
