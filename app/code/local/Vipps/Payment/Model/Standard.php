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

use Vipps\Payment\Gateway\Command\CommandManager;

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

        $this->commandManager = new CommandManager();
    }

    /**
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return \Mage::getUrl('vipps/payment_regular');
    }

    public function refund($payment, $baseAmountToRefund)
    {
        parent::refund();

        $this->commandManager->refund($payment, $baseAmountToRefund);

        return $this;
    }
}
