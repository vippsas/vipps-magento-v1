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

class Vipps_Payment_Adminhtml_Vipps_RequestProfilingController extends Mage_Adminhtml_Controller_Action
{
    /** @var Vipps_Payment_Model_Adapter_MessageManager */
    private $messageManager;

    /**
     * @return bool
     * @throws Mage_Core_Exception
     */
    protected function _isAllowed()
    {
        return parent::_isAllowed() && Mage::getSingleton('admin/session')->isAllowed('admin/system/Vipps_Payment');
    }

    public function preDispatch()
    {
        parent::preDispatch();

        $this->messageManager = Mage::getSingleton('vipps_payment/adapter_messageManager');

        return $this;
    }

    /**
     *
     */
    public function indexAction()
    {
        $this->_title($this->__("Vipps Payment"));
        $this->_title($this->__("Request Profiling"));

        $this->loadLayout();

        $this->_setActiveMenu('Vipps_Payment/request_profiling');

        $this->renderLayout();
    }

    /**
     * @return Mage_Core_Controller_Varien_Action|Vipps_Payment_Adminhtml_Vipps_QuoteMonitoringController
     */
    public function viewAction()
    {
        $this->_title($this->__("Vipps Payment"));
        $this->_title($this->__("View Profiling Item"));

        try {
            $profilingItem = Mage::getModel('vipps_payment/profiling_item')->load($this->getRequest()->getParam('entity_id'));
            Mage::register('vipps_profiling_item', $profilingItem);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('*/*/*');
        }

        $this
            ->loadLayout()
            ->renderLayout();
    }
}
