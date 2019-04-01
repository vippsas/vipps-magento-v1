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
 * Class Vipps_Payment_Block_Express_Button
 */
class Vipps_Payment_Block_Express_Product extends Mage_Core_Block_Template
{
    /**
     * @var string
     */
    protected $_template = 'vippspayment/expressaction.phtml';

    /**
     * @var Vipps_Payment_Helper_Express
     */
    protected $helper;

    /**
     * @var Vipps_Payment_Gateway_Config_Config
     */
    protected $config;

    /**
     * Vipps_Payment_Block_Express_Button constructor.
     * @param array $args
     * @throws Mage_Core_Exception
     */
    public function __construct(array $args = array())
    {
        parent::__construct($args);

        /** @var Vipps_Payment_Helper_Gateway $helper */
        $helper = $this->helper('vipps_payment/gateway');
        /** @var Vipps_Payment_Gateway_Config_Config gatewayConfig */
        $this->config = $helper->getSingleton('config_config');
        $this->helper = $this->helper('vipps_payment/express');
    }

    /**
     * @return string
     */
    public function getDataOptions()
    {
        return $this
            ->helper('core')
            ->jsonEncode(array('redirectUrl' => $this->escapeUrl($this->helper->getExpressCheckoutUrl())));
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->helper->isActive()) {
            return '';
        }

        return parent::_toHtml();
    }
}
