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

class Vipps_Payment_Adminhtml_Vipps_QuoteMonitoringController extends Mage_Adminhtml_Controller_Action
{
    /** @var Vipps_Payment_Model_QuoteRepository */
    protected $quoteRepository;

    /** @var Vipps_Payment_Model_Adapter_MessageManager */
    protected $messageManager;

    /** @var Vipps_Payment_Model_Quote_Command_ManualCancelFactory */
    private $manualCancelFactory;

    /** @var Vipps_Payment_Model_Quote_Command_RestartFactory */
    private $restartFactory;

    /**
     * @return Mage_Adminhtml_Controller_Action|void
     * @throws Mage_Core_Exception
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->quoteRepository = Mage::getSingleton('vipps_payment/quoteRepository');
        $this->messageManager = Mage::getSingleton('vipps_payment/adapter_messageManager');
        $this->manualCancelFactory = Mage::getSingleton('vipps_payment/quote_command_manualCancelFactory');
        $this->restartFactory = Mage::getSingleton('vipps_payment/quote_command_restartFactory');

        return $this;

    }

    /**
     *
     */
    public function indexAction()
    {
        $this->_title($this->__("Quote Monitoring"));

        $this->loadLayout();

        $this->_setActiveMenu('Vipps_Payment/quote_monitoring');

        $this->renderLayout();
    }

    /**
     * @return Mage_Core_Controller_Varien_Action|Vipps_Payment_Adminhtml_Vipps_QuoteMonitoringController
     */
    public function viewAction()
    {
        $this->_title($this->__("View Quote"));

        try {
            $vippsQuote = $this->quoteRepository->load($this->getRequest()->getParam('entity_id'));
            Mage::register('vipps_quote', $vippsQuote);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('*/*/*');
        }

        $this
            ->loadLayout()
            ->renderLayout();
    }

    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    public function restartAction()
    {
        try {
            $this
                ->getRestart()
                ->execute();
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->_redirectReferer();
    }

    /**
     * @return Vipps_Payment_Model_Quote_Command_Restart
     * @throws Mage_Core_Exception
     */
    private function getRestart()
    {
        $vippsQuote = $this
            ->quoteRepository
            ->load($this->getRequest()->getParam('entity_id'));

        return $this->restartFactory->create($vippsQuote);
    }

    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    public function cancelAction()
    {
        try {
            $this
                ->getManualCancelCommand()
                ->execute();
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->_redirectReferer();
    }

    /**
     * @return Vipps_Payment_Model_Quote_Command_ManualCancel
     * @throws Mage_Core_Exception
     */
    private function getManualCancelCommand()
    {
        $vippsQuote = $this
            ->quoteRepository
            ->load($this->getRequest()->getParam('entity_id'));

        return $this->manualCancelFactory->create($vippsQuote);
    }

    /**
     * @return bool
     * @throws Mage_Core_Exception
     */
    protected function _isAllowed()
    {
        return parent::_isAllowed() && Mage::getSingleton('admin/session')->isAllowed('admin/system/Vipps_Payment');
    }
}
