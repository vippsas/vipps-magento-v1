<?php

/**
 * Copyright 2020 Vipps
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
class Vipps_Payment_Model_Cron_AbstractCron
{
    /**
     * Order collection page size
     */
    const COLLECTION_PAGE_SIZE = 100;

    /** @var Vipps_Payment_Model_Adapter_Logger  */
    protected $logger;

    /** @var Mage_Core_Model_App_Emulation */
    protected $storeEmulation;

    /**
     * @var Vipps_Payment_Gateway_Command_CommandManager
     */
    protected $commandManager;

    /**
     * @var Vipps_Payment_Model_Order_Cancellation_Config
     */
    protected $cancellationConfig;

    public function __construct()
    {
        $this->logger = Mage::getSingleton('vipps_payment/adapter_logger');
        $this->storeEmulation = Mage::getSingleton('core/app_emulation');
        $this->commandManager = Mage::helper('vipps_payment/gateway')->getSingleton('command_commandManager');
        $this->cancellationConfig = Mage::getSingleton('vipps_payment/order_cancellation_config');
    }
}
