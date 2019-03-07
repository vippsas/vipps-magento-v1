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
 * Class LockManager
 * @package Vipps\Payment\Model
 */
class Vipps_Payment_Model_Helper_LockManager
{
    /**
     * @var Vipps_Payment_Model_Adapter_ResourceConnectionProvider
     */
    private $resource;

    /**
     * @var string
     */
    private $prefix;

    /**
     * Holds current lock name if set, otherwise false
     * @var string|false
     */
    private $currentLock = false;

    /**
     * Database constructor.
     *
     * @throws Mage_Core_Exception
     */
    public function __construct() {
        $this->resource = Mage::getSingleton('vipps_payment/adapter_resourceConnectionProvider');
    }

    /**
     * Sets a lock for name
     *
     * @param string $name lock name
     * @param int $timeout How long to wait lock acquisition in seconds, negative value means infinite timeout
     * @return bool
     * @throws \Mage_Core_Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function lock($name, $timeout = -1)
    {
        $name = $this->addPrefix($name);

        /**
         * Before MySQL 5.7.5, only a single simultaneous lock per connection can be acquired.
         * This limitation can be removed once MySQL minimum requirement has been raised,
         * currently we support MySQL 5.6 way only.
         */
        if ($this->currentLock) {
            \Mage::throwException(
                sprintf(
                    'Current connection is already holding lock for $1, only single lock allowed',
                    $this->currentLock
                )
            );
        }

        $result = (bool)$this->resource
            ->getConnection()
            ->query("SELECT GET_LOCK(?, ?);", [(string)$name, (int)$timeout])
            ->fetchColumn();

        if ($result === true) {
            $this->currentLock = $name;
        }

        return $result;
    }

    /**
     * Adds prefix and checks for max length of lock name
     *
     * Limited to 64 characters in MySQL.
     *
     * @param string $name
     * @return string $name
     * @throws \Mage_Core_Exception
     */
    private function addPrefix($name)
    {
        $name = $this->getPrefix() . '_' . $name;

        if (strlen($name) > 64) {
            \Mage::throwException(sprintf('Lock name too long: %s...', substr($name, 0, 64)));
        }

        return $name;
    }

    /**
     * Get installation specific lock prefix to avoid lock conflicts
     *
     * @return string lock prefix
     */
    private function getPrefix()
    {
        if ($this->prefix === null) {
            $this->prefix = $this->resource->getTablePrefix();
        }

        return $this->prefix;
    }

    /**
     * Releases a lock for name
     *
     * @param string $name lock name
     * @return bool
     * @throws \Mage_Core_Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function unlock($name)
    {
        $name = $this->addPrefix($name);

        $result = (bool)$this->resource->getConnection()->query(//@codingStandardsIgnoreLine
            "SELECT RELEASE_LOCK(?);",//@codingStandardsIgnoreLine
            [(string)$name]
        )->fetchColumn();

        if ($result === true) {
            $this->currentLock = false;
        }

        return $result;
    }

    /**
     * Tests of lock is set for name
     *
     * @param string $name lock name
     * @return bool
     * @throws \Mage_Core_Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function isLocked($name)
    {
        $name = $this->addPrefix($name);

        return (bool)$this->resource->getConnection()->query(//@codingStandardsIgnoreLine
            "SELECT IS_USED_LOCK(?);",//@codingStandardsIgnoreLine
            [(string)$name]
        )->fetchColumn();
    }
}
