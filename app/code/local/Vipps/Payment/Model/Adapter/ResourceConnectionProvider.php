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
 * Class ResourceConnectionProvider
 * @package Vipps\Payment\Model\Adapter
 */
class Vipps_Payment_Model_Adapter_ResourceConnectionProvider
{
    /**
     * @return \Varien_Db_Adapter_Pdo_Mysql
     */
    public function getConnection()
    {
        return \Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    /**
     * @param $alias
     * @return string
     */
    public function getTableName($alias)
    {
        return $this->getResource()->getTableName($alias);
    }

    /**
     * @return \Mage_Core_Model_Resource
     */
    protected function getResource()
    {
        return \Mage::getSingleton('core/resource');
    }

    /**
     * @return string
     */
    public function getTablePrefix()
    {
        return (string)\Mage::getConfig()->getTablePrefix();
    }
}
