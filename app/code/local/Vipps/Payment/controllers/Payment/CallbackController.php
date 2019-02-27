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
 * Class Callback
 * @package Vipps\Payment\Controller\Payment
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vipps_Payment_Payment_CallbackController extends \Vipps_Payment_Controller_Abstract
{
    /**
     * @var Vipps_Payment_Model_OrderPlace
     */
    private $orderPlace;

    /**
     * @var Vipps_Payment_Model_QuoteLocator
     */
    private $quoteLocator;

    /**
     * @var \Mage_Sales_Model_Quote
     */
    private $quote;

    /**
     * @return $this|\Mage_Core_Controller_Front_Action|\Vipps_Payment_Controller_Abstract
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->orderPlace = Mage::getSingleton('vipps_payment/orderPlace');
        $this->quoteLocator = Mage::getSingleton('vipps_payment/quoteLocator');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function indexAction()
    {
        $result = [];
        try {
            $requestData = $this->serializer->unserialize($this->getRequest()->getRawBody());

            $this->authorize($requestData);
            $transaction = $this->transactionBuilder->setData($requestData)->build();
            $this->orderPlace->execute($this->getQuote($requestData), $transaction);

            $this->getResponse()->setHttpResponseCode(self::STATUS_CODE_200);
            $result = ['status' => self::STATUS_CODE_200, 'message' => 'success'];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->getResponse()->setHttpResponseCode(self::STATUS_CODE_500);
            $result = [
                'status'  => self::STATUS_CODE_500,
                'message' => 'An error occurred during callback processing. ' . $e->getMessage()
            ];
        } finally {
            $compliant = $this->gdprCompliance->process($this->getRequest()->getRawBody());
            $this->logger->debug($compliant);
        }

        $this->_renderJson($result);
    }

    /**
     * @param $requestData
     *
     * @return bool
     * @throws \Exception
     */
    private function authorize($requestData)
    {
        if (!$this->isValid($requestData)) {
            throw new \Exception(__('Invalid request parameters'), 400); //@codingStandardsIgnoreLine
        }
        if (!$this->isAuthorized($requestData)) {
            throw new \Exception(__('Invalid request'), 401); //@codingStandardsIgnoreLine
        }
        return true;
    }

    /**
     * Method to validate request body parameters.
     *
     * @param array $requestData
     *
     * @return bool
     * @throws \Zend_Controller_Request_Exception
     */
    private function isValid($requestData)
    {
        return array_key_exists('orderId', $requestData)
            && array_key_exists('transactionInfo', $requestData)
            && array_key_exists('status', $requestData['transactionInfo'])
            && $this->getRequest()->getHeader('authorization');
    }

    /**
     * @param array $requestData
     *
     * @return bool
     * @throws \Zend_Controller_Request_Exception
     */
    private function isAuthorized($requestData)
    {
        $quote = $this->getQuote($requestData);
        if ($quote) {
            $additionalInfo = $quote->getPayment()->getAdditionalInformation();
            $authToken = isset($additionalInfo[Vipps_Payment_Gateway_Request_Initiate_MerchantDataBuilder::MERCHANT_AUTH_TOKEN]) ?
                $additionalInfo[Vipps_Payment_Gateway_Request_Initiate_MerchantDataBuilder::MERCHANT_AUTH_TOKEN]
                : null;

            if ($authToken === $this->getRequest()->getHeader('authorization')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve a quote from repository based on request parameter order id
     *
     * @param $requestData
     *
     * @return \Mage_Sales_Model_Quote|null
     */
    private function getQuote($requestData)
    {
        if (null === $this->quote) {
            $this->quote = $this->quoteLocator->get($this->getOrderId($requestData)) ?: null;
        }
        return $this->quote;
    }

    /**
     * Return order id
     *
     * @param $requestData
     *
     * @return string|null
     */
    private function getOrderId($requestData)
    {
        return isset($requestData['orderId']) ? $requestData['orderId'] : null;
    }
}
