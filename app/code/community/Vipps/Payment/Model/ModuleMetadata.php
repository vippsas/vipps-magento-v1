<?php
/**
 * Copyright 2021 Vipps
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 *  documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 *  the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 *  and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 *  TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 *  THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 *  CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 *  IN THE SOFTWARE.
 *
 */

class Vipps_Payment_Model_ModuleMetadata
{
    /**
     * The name of the module for the optional Vipps HTTP headers.
     *
     * @var string
     */
    const MODULE_NAME = 'vipps-magento-v1';
    
    /**
     * @inheritDoc
     */
    public function addOptionalHeaders($headers)
    {
        $additionalHeaders = [
            'Vipps-System-Name' => $this->getSystemName(),
            'Vipps-System-Version' => $this->getSystemVersion(),
            'Vipps-System-Plugin-Name' => $this->getModuleName(),
            'Vipps-System-Plugin-Version' => $this->getModuleVersion(),
        ];

        return array_merge($headers, $additionalHeaders);
    }

    /**
     * Get system name, magento in out case.
     *
     * @return string
     */
    private function getSystemName()
    {
        return sprintf(
            'Magento 1 %s',
            Mage::getEdition()
        );
    }
    
    /**
     * Get the system version (eg. 1.9.4.x).
     *
     * @return string
     */
    private function getSystemVersion()
    {
        return Mage::getVersion();
    }

    /**
     * Get the name of the current module (`vipps-magento-v1`).
     *
     * @return string
     */
    private function getModuleName()
    {
        return self::MODULE_NAME;
    }

    /**
     * Get the name of the current module (`vipps-magento-v1`).
     *
     * @return string
     */
    private function getModuleVersion()
    {
        return (string)Mage::getConfig()->getNode('modules/Vipps_Payment/version');
    }
}