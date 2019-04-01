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
 * Class Vipps_Payment_Helper_Gateway
 */
class Vipps_Payment_Helper_Gateway
{
    /**
     * @const string
     */
    const GATEWAY_GROUP = 'gateway';

    /**
     * @const string
     */
    const MODEL_PREFIX = 'vipps_payment/';

    /**
     * Coverts data_paymentDataObjectFactory to Vipps_Payment_Gateway_Data_PaymentDataObjectFactory
     *
     * @param $type
     * @param array $arguments
     * @return bool
     */
    public function getModel($type, $arguments = array())
    {
        $modelClass = self::MODEL_PREFIX . $type;

        $className = Mage::getConfig()->getGroupedClassName(self::GATEWAY_GROUP, $modelClass);
        if (class_exists($className)) {
            return new $className($arguments);
        }
        
        return false;
    }

    /**
     * @param $modelClass
     * @param array $arguments
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getSingleton($modelClass, $arguments = array())
    {
        $registryKey =  'vipps_gateway_singleton/' . $modelClass;
        if (!Mage::registry($registryKey)) {
            Mage::register($registryKey, self::getModel($modelClass, $arguments));
        }
        return Mage::registry($registryKey);

    }
}
