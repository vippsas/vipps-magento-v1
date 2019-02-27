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

/**
 * Class Curl
 */
class Vipps_Payment_Gateway_Http_Client_Curl implements Vipps_Payment_Gateway_Http_Client_ClientInterface
{
    /**
     * @var \Vipps_Payment_Gateway_Config_Config
     */
    private $config;

    /**
     * @var \Vipps_Payment_Model_TokenProvider
     */
    private $tokenProvider;

    /**
     * @var
     */
    private $jsonEncoder;

    /**
     * @var \Vipps_Payment_Model_Adapter_Logger
     */
    private $logger;

    /**
     * Curl constructor.
     *
     */
    public function __construct()
    {
        $this->config = Mage::helper('vipps_payment/gateway')->getSingleton('config_config');
        $this->tokenProvider = Mage::getModel('vipps_payment/tokenProvider');
        $this->jsonEncoder = Mage::getSingleton('vipps_payment/adapter_jsonEncoder');
        $this->logger = Mage::getSingleton('vipps_payment/adapter_logger');
    }

    /**
     * @param Vipps_Payment_Gateway_Http_Transfer $transfer
     *
     * @return array|string
     * @throws \Exception
     */
    public function placeRequest(Vipps_Payment_Gateway_Http_Transfer $transfer)
    {
        try {
            /** @var \Zend_Http_Response $response */
            $response = $this->place($transfer);
            if ($response->getStatus() == '401') {
                $this->tokenProvider->regenerate();
                $response = $this->place($transfer);
            }

            return ['response' => $response];
        } catch (\Exception $t) {
            $this->logger->critical($t->__toString());
            throw new \Exception($t->getMessage(), $t->getCode(), $t);
        }
    }

    /**
     * @param Transfer $transfer
     *
     * @return \Zend_Http_Response
     * @throws Vipps_Payment_Gateway_Exception_AuthenticationException
     */
    private function place(Vipps_Payment_Gateway_Http_Transfer $transfer)
    {
        try {
            $adapter = Mage::getModel('vipps_payment/adapter_curl');

            $options = $this->getBasicOptions();
            if ($transfer->getMethod() === \Zend_Http_Client::PUT) {
                $options = $options + [
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
            isset($adapter) ? $adapter->close() : null;
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
     * @throws Vipps_Payment_Gateway_Exception_AuthenticationException
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
