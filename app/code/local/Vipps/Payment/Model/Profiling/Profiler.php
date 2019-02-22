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

namespace Vipps\Payment\Model\Profiling;

use Vipps\Payment\Gateway\Http\Transfer;
use Vipps\Payment\Model\Adapter\JsonEncoder;
use Vipps\Payment\Model\Profiling\TypeInterface;
use Vipps\Payment\Model\Gdpr\Compliance;

class Profiler
{
    /**
     * @var \Vipps\Payment\Model\Adapter\Config
     */
    private $config;

    /**
     * @var ItemFactory
     */
    private $dataItemFactory;

    /**
     * @var ItemRepository
     */
    private $itemRepository;

    /**
     * @var JsonEncoder
     */
    private $jsonDecoder;

    /**
     * @var Compliance
     */
    private $gdprCompliance;

    /**
     * Profiler constructor.
     *
     */
    public function __construct()
    {
        $this->config = new \Vipps\Payment\Gateway\Config\Config();
        $this->dataItemFactory = new ItemFactory();
        $this->itemRepository = new ItemRepository();
        $this->jsonDecoder = new JsonEncoder();
        $this->gdprCompliance = new Compliance();
    }

    /**
     * @param Transfer $transfer
     * @param \Zend_Http_Response $response
     *
     * @return string|null
     */
    public function save(Transfer $transfer, \Zend_Http_Response $response)
    {
        if (!$this->isProfilingEnabled()) {
            return null;
        }
        /** @var ItemInterface $itemDO */
        $itemDO = $this->dataItemFactory->create();

        $data = $this->parseDataFromTransferObject($transfer);

        $requestType = isset($data['type']) ? $data['type'] : 'undefined';
        $orderId = isset($data['order_id']) ? $data['order_id'] : $this->parseOrderId($response);

        $itemDO->setRequestType($requestType);
        $itemDO->setRequest($this->packArray(
            array_merge(['headers' => $transfer->getHeaders()], ['body' => $transfer->getBody()])
        ));

        $itemDO->setStatusCode($response->getStatus());
        $itemDO->setIncrementId($orderId);
        $itemDO->setResponse($this->packArray($this->parseResponse($response)));

        $item = $this->itemRepository->save($itemDO);
        return $item->getEntityId();
    }

    /**
     * Check whether profiler enabled or not
     *
     * @return bool
     */
    private function isProfilingEnabled()
    {
        return (bool)$this->config->getValue('profiling');
    }

    /**
     * Parse data from transfer object
     *
     * @param TransferInterface $transfer
     *
     * @return array
     */
    private function parseDataFromTransferObject(Transfer $transfer)
    {
        $result = [];
        if (preg_match('/payments(\/([^\/]+)\/([a-z]+))?$/', $transfer->getUri(), $matches)) {
            $result['order_id'] = isset($matches[2]) ? $matches[2] : (isset($transfer->getBody()['transaction']['orderId']) ? $transfer->getBody()['transaction']['orderId'] : null);
            $result['type'] = isset($matches[3]) ? $matches[3] : TypeInterface::INITIATE_PAYMENT;
        }
        return $result;
    }

    /**
     * Parse order id from response object
     *
     * @param Response $response
     *
     * @return string|null
     */
    private function parseOrderId(\Zend_Http_Response $response)
    {
        $content = $this->jsonDecoder->decode($response->getBody());
        return isset($content['orderId']) ? $content['orderId'] : null;
    }

    /**
     * @param $data
     *
     * @return string
     */
    private function packArray($data)
    {
        $recursive = function ($data, $indent = '') use (&$recursive) {
            $output = '{' . PHP_EOL;
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $output .= $indent . '    ' . $key . ': ' . $recursive($value, $indent . '    ') . PHP_EOL;
                } elseif (!is_object($value)) {
                    $output .= $indent . '    ' . $key . ': ' . ($value ? $value : '""') . PHP_EOL;
                }
            }
            $output .= $indent . '}' . PHP_EOL;
            return $output;
        };

        $output = $recursive($data);
        return $output;
    }

    /**
     * Parse response data for profiler from response
     *
     * @param Response $response
     *
     * @return array
     */
    private function parseResponse(\Zend_Http_Response $response)
    {
        return $this->depersonalizedResponse($this->jsonDecoder->decode($response->getBody()));
    }

    /**
     * Depersonalize response
     *
     * @param array $response
     *
     * @return array
     */
    private function depersonalizedResponse($response)
    {
        unset($response['url']);

        return $this->gdprCompliance->process($response);
    }
}
