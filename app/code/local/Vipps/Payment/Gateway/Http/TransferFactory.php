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
 * Class TransferFactory
 * @package Vipps\Payment\Gateway\Http
 */
class Vipps_Payment_Gateway_Http_TransferFactory
{
    /**
     * @var string
     */
    private $endpointUrl;

    /**
     * @var string
     */
    private $method;

    /**
     * @var Vipps_Payment_Gateway_Http_TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var \Mage_Core_Model_Url
     */
    private $urlResolver;

    /**
     * @var array
     */
    private $urlParams = [];

    /**
     * @var array
     */
    private $allowedFields = [
        'orderId',
        'customerInfo',
        'merchantInfo',
        'transaction',
    ];

    /**
     * TransferFactory constructor.
     *
     * @param string $method
     * @param string $endpointUrl
     * @param array $urlParams
     * @throws Mage_Core_Exception
     */
    public function __construct(
        $method,
        $endpointUrl,
        array $urlParams = []
    ) {
        $this->transferBuilder = new Vipps_Payment_Gateway_Http_TransferBuilder();
        $this->urlResolver = Mage::getSingleton('vipps_payment/urlResolver');
        $this->method = $method;
        $this->endpointUrl = $endpointUrl;
        $this->urlParams = $urlParams;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     *
     * @return Vipps_Payment_Gateway_Http_Transfer
     */
    public function create(array $request)
    {
        $this->transferBuilder->setHeaders(array(
            Vipps_Payment_Gateway_Http_Client_ClientInterface::HEADER_PARAM_X_REQUEST_ID => isset($request['requestId'])
                ? $request['requestId']
                : $this->generateRequestId()
        ));

        $request = $this->filterPostFields($request);

        if (isset($request['requestId'])) {
            unset($request['requestId']);
        }

        $this->transferBuilder
            ->setBody($this->getBody($request))
            ->setMethod($this->method)
            ->setUri($this->getUrl($request));

        return $this->transferBuilder->build();
    }

    /**
     * Generate value of request id for current request
     *
     * @return string
     */
    private function generateRequestId()
    {
        return uniqid('req-id-', true);
    }

    /**
     * Remove all fields that are not marked as allowed.
     *
     * @param array $fields
     * @return array
     */
    private function filterPostFields($fields)
    {
        $allowedFields = $this->allowedFields;

        return array_intersect_key($fields, array_flip($allowedFields));
    }

    /**
     * Method to get needed content body from request.
     *
     * @param array $request
     *
     * @return array
     */
    private function getBody(array $request = [])
    {
        foreach ($this->urlParams as $paramValue) {
            if (isset($request[$paramValue])) {
                unset($request[$paramValue]);
            }
        }

        return $request;
    }

    /**
     * Generating Url.
     *
     * @param $request
     *
     * @return string
     */
    private function getUrl(array $request = [])
    {
        $endpointUrl = $this->endpointUrl;
        /** Binding url parameters if they were specified */
        foreach ($this->urlParams as $paramValue) {
            if (isset($request[$paramValue])) {
                $endpointUrl = str_replace(':' . $paramValue, $request[$paramValue], $this->endpointUrl);
            }
        }
        return $this->urlResolver->getUrl($endpointUrl);
    }
}
