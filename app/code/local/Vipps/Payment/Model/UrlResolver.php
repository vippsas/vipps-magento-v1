<?php
/**
 * Copyright 2018 Vipps
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
namespace Vipps\Payment\Model\Adapter;

use Vipps\Payment\Gateway\Config\Config;

/**
 * Class UrlResolver
 * @package Vipps\Payment\Model
 */
class UrlResolver
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
     * @var Config
     */
    private $config;

    /**
     * VippsUrlProvider constructor.
     */
    public function __construct() {
        $this->config = new Config();
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

    public function getUrl($url)
    {
        return $this->getBaseUrl() . $url;
    }
}
