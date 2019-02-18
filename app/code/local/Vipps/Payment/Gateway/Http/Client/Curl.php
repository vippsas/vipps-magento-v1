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

namespace Vipps\Payment\Gateway\Http\Client;

use Vipps\Payment\Gateway\Config\Config;
use Vipps\Payment\Gateway\Exception\AuthenticationException;
use Vipps\Payment\Gateway\Http\Transfer;
use Vipps\Payment\Model\Adapter\TokenProvider;
use Vipps\Payment\Model\Adapter\TokenProviderInterface;

/**
 * Class Curl
 * @package Vipps\Payment\Gateway\Http\Client
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class Curl implements ClientInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var TokenProviderInterface
     */
    private $tokenProvider;

    /**
     * @var
     */
    private $jsonEncoder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Curl constructor.
     *
     */
    public function __construct()
    {
        $this->config = new Config();
        $this->tokenProvider = new TokenProvider();
        $this->jsonEncoder = new \Vipps\Payment\Model\Adapter\Adapter\JsonEncoder();
        $this->logger = \Mage::getSingleton('vipps_payment/logger');
    }

    /**
     * @param TransferInterface $transfer
     *
     * @return array|string
     * @throws \Exception
     */
    public function placeRequest(Transfer $transfer)
    {
        try {
            $response = $this->place($transfer);
            if ($response->getStatusCode() == '401') {
                $this->tokenProvider->regenerate();
                $response = $this->place($transfer);
            }

            return ['response' => $response];
        } catch (\Throwable $t) {
            $this->logger->critical($t->__toString());
            throw new \Exception($t->getMessage(), $t->getCode(), $t);
        }
    }

    /**
     * @param TransferInterface $transfer
     *
     * @return ZendResponse
     * @throws AuthenticationException
     */
    private function place(Transfer $transfer)
    {
        try {
            $adapter = new \Zend_Http_Client_Adapter_Curl();

            $options = $this->getBasicOptions();
            if ($transfer->getMethod() === \Zend_Http_Client::PUT) {
                $options = $options +
                    [
                        \CURLOPT_RETURNTRANSFER => true,
                        \CURLOPT_CUSTOMREQUEST  => \Zend_Http_Client::PUT,
                        \CURLOPT_POSTFIELDS     => $this->jsonEncoder->encode($transfer->getBody())
                    ];
            }
            $adapter->setOptions($options);
            // send request
            $adapter->write(
                $transfer->getMethod(),
                $transfer->getUri(),
                '1.1',
                $this->getHeaders($transfer->getHeaders()),
                $this->jsonEncoder->encode($transfer->getBody())
            );
            $responseSting = $adapter->read();
            $response = \Zend_Http_Response::fromString($responseSting);

            return $response;
        } finally {
            $adapter ? $adapter->close() : null;
        }
    }

    /**
     * @return array
     */
    private function getBasicOptions()
    {
        return [
            CURLOPT_TIMEOUT => 30,
        ];
    }

    /**
     * @param $headers
     *
     * @return array
     * @throws AuthenticationException
     */
    private function getHeaders($headers)
    {
        $headers = array_merge(
            [
                self::HEADER_PARAM_CONTENT_TYPE     => 'application/json',
                self::HEADER_PARAM_AUTHORIZATION    => 'Bearer ' . $this->tokenProvider->get(),
                self::HEADER_PARAM_X_REQUEST_ID     => '',
                self::HEADER_PARAM_X_SOURCE_ADDRESS => '',
                self::HEADER_PARAM_X_TIMESTAMP      => '',
                self::HEADER_PARAM_SUBSCRIPTION_KEY => $this->config->getValue('subscription_key2')
            ],
            $headers
        );

        $result = [];
        foreach ($headers as $key => $value) {
            $result[] = sprintf('%s: %s', $key, $value);
        }

        return $result;
    }
}
