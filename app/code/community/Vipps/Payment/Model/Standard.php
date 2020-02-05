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
 * Payment source.
 *
 * Class Vipps_Payment_Model_Standard
 */
class Vipps_Payment_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    /**
     * @const Method code.
     */
    const CODE = 'vipps';

    /**
     * @var string
     */
    protected $_code = self::CODE;
    protected $_formBlockType = 'vipps_payment/form';


    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canFetchTransactionInfo = true;

    private $commandManager;

    public function __construct()
    {
        parent::__construct();

        $this->commandManager = Mage::helper('vipps_payment/gateway')->getSingleton('command_commandManager');
    }

    /**
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return Mage::getUrl('vipps/payment_regular');
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this|Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        parent::refund($payment, $amount);

        $this->commandManager->refund($payment, $amount);

        return $this;
    }

    /**
     * @param Varien_Object $payment
     * @param float $amount
     * @return Vipps_Payment_Model_Standard
     */
    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);

        $this->commandManager->capture($payment, $amount);

        return $this;
    }

    /**
     * Cancel payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Vipps_Payment_Model_Standard
     */
    public function cancel(Varien_Object $payment)
    {
        parent::cancel($payment);

        $this->commandManager->cancel($payment);

        return $this;
    }

    /**
     * @param null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return (bool)(int)$this->getConfigData('enabled', $quote ? $quote->getStoreId() : null);
    }
}
