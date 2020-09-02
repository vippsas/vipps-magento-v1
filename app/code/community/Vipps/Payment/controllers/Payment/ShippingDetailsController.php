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
 * Class ShippingDetails
 * @package Vipps\Payment\Controller\Payment
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Vipps_Payment_Payment_ShippingDetailsController extends \Vipps_Payment_Controller_Abstract
{
    /**
     * @var Vipps_Payment_Model_QuoteLocator
     */
    private $quoteLocator;

    /**
     * @var Vipps_Payment_Model_Adapter_ShippingMethodManagement
     */
    private $shipmentEstimation;

    /**
     * @var Vipps_Payment_Model_Quote_AddressUpdater
     */
    private $addressUpdater;

    /**
     * ShippingDetails constructor.
     *
     * @return Vipps_Payment_Payment_ShippingDetailsController
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->quoteLocator = Mage::getSingleton('vipps_payment/quoteLocator');
        $this->shipmentEstimation = Mage::getSingleton('vipps_payment/adapter_shippingMethodManagement');
        $this->addressUpdater = Mage::getSingleton('vipps_payment/quote_addressUpdater');

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     * @throws \Zend_Controller_Response_Exception
     */
    public function indexAction()
    {
        $response = $this->getResponse();
        $responseData = [];
        try {
            $reservedOrderId = $this->getReservedOrderId();
            $quote = $this->getQuote($reservedOrderId);

            $vippsAddress = $this->serializer->unserialize($this->getRequest()->getRawBody());
            $address = Mage::getModel('sales/quote_address');
            $address->addData([
                'postcode'     => $vippsAddress['postCode'],
                'street'       => $vippsAddress['addressLine1'] . PHP_EOL . $vippsAddress['addressLine2'],
                'address_type' => 'shipping',
                'city'         => $vippsAddress['city'],
                'country_id'   => Vipps_Payment_Gateway_Transaction_ShippingDetails::NORWEGIAN_COUNTRY_ID
            ]);
            /**
             * As Quote is deactivated, so we need to activate it for estimating shipping methods
             */
            $this->addressUpdater->fromSourceAddress($quote, $address);
            $quote->setIsActive(true);
            $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress($quote, $address);
            $responseData = [
                'addressId'       => $vippsAddress['addressId'],
                'orderId'         => $reservedOrderId,
                'shippingDetails' => []
            ];
            foreach ($shippingMethods as $key => $shippingMethod) {
                $methodFullCode = $shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode();

                $responseData['shippingDetails'][] = [
                    'isDefault'        => 'N',
                    'priority'         => $key,
                    'shippingCost'     => $shippingMethod->getAmount(),
                    'shippingMethod'   => $shippingMethod->getMethodCode(),
                    'shippingMethodId' => $methodFullCode,
                ];
            }
            $response->setHttpResponseCode(self::STATUS_CODE_200);
        } catch (Mage_Core_Exception $e) {
            $this->logger->critical($e->getMessage());
            $response->setHttpResponseCode(self::STATUS_CODE_500);
            $responseData = [
                'status'  => self::STATUS_CODE_500,
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $response->setHttpResponseCode(self::STATUS_CODE_500);
            $responseData = [
                'status'  => self::STATUS_CODE_500,
                'message' => __('An error occurred during Shipping Details processing.')
            ];
        } finally {
            $compliantString = $this->gdprCompliance->process($this->getRequest()->getRawBody());
            $this->logger->debug($compliantString);
        }

        $this->_renderJson($responseData);
    }

    /**
     * Get reserved order id from request url
     *
     * @return int|null|string
     */
    private function getReservedOrderId()
    {
        $params = $this->getRequest()->getParams();
        next($params);
        $reservedOrderId = key($params);

        return $reservedOrderId;
    }

    /**
     * Retrieve quote object
     *
     * @param $reservedOrderId
     *
     * @return Mage_Sales_Model_Quote
     * @throws Mage_Core_Exception
     */
    private function getQuote($reservedOrderId)
    {
        $quote = $this->quoteLocator->get($reservedOrderId);
        if (!$quote) {
            Mage::throwException(__('Requested quote not found'));
        }
        return $quote;
    }
}
