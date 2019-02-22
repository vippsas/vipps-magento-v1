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

use Vipps\Payment\Gateway\Exception\VippsException;
use Vipps\Payment\Gateway\Request\Initiate\InitiateBuilderInterface;

class Vipps_Payment_ExpressController extends Vipps_Payment_Controller_Abstract
{
    public function indexAction()
    {
        try {
            if (!$this->config->getValue('express_checkout')) {
                throw new Mage_Core_Exception(__('Express Payment method is not available.'));
            }
            $quote = $this->cart->getQuote();
            $responseData = $this->commandManager->initiatePayment(
                $quote->getPayment(),
                [
                    'amount'                                   => $quote->getGrandTotal(),
                    InitiateBuilderInterface::PAYMENT_TYPE_KEY => InitiateBuilderInterface::PAYMENT_TYPE_EXPRESS_CHECKOUT
                ]
            );

            return $this->_redirectUrl($responseData['url']);
        } catch (VippsException $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred during request to Vipps. Please try again later.')
            );
        }

        return $this->_redirect('checkout/cart', ['_secure' => true]);

    }
}
