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
 * Class UrlResolver
 */
class Vipps_Payment_Model_UrlResolver
{
    /**
     * @var string
     */
    private static $productionBaseUrl = 'https://api.vipps.no';

    /**
     * @var string
     */
    private static $developBaseUrl = 'https://apitest.vipps.no';

    /**
     * @var \Vipps_Payment_Gateway_Config_Config
     */
    private $config;

    /**
     * VippsUrlProvider constructor.
     */
    public function __construct() {
        $this->config = Mage::helper('vipps_payment/gateway')->getSingleton('config_config');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getBaseUrl()
    {
        $env = $this->config->getValue('environment');

        return $env === \Vipps_Payment_Model_System_Config_Source_Environment::ENVIRONMENT_DEVELOP
            ? self::$developBaseUrl
            : self::$productionBaseUrl;
    }

    /**
     * @param $url
     * @return string
     */
    public function getUrl($url)
    {
        return $this->getBaseUrl() . $url;
    }
}
