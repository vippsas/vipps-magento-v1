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

class Vipps_Payment_Helper_Express extends Mage_Core_Helper_Abstract
{
    /**
     * @var Vipps_Payment_Gateway_Config_Config
     */
    private $config;

    public function __construct()
    {
        /** @var Vipps_Payment_Helper_Gateway $helper */
        $helper = Mage::helper('vipps_payment/gateway');
        /** @var Vipps_Payment_Gateway_Config_Config gatewayConfig */
        $this->config = $helper->getSingleton('config_config');
    }

    /**
     * @return string
     */
    public function getExpressCheckoutUrl()
    {
        return Mage::getUrl('vipps/express');
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->config->getValue('active') && $this->config->getValue('express_checkout');
    }
}
