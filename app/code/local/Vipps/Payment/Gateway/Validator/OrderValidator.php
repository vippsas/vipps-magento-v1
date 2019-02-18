<?php
/**
 * Copyright 2018 Vipps
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
namespace Vipps\Payment\Gateway\Validator;

use Vipps\Payment\Gateway\Request\SubjectReader;

/**
 * Class OrderValidator
 * @package Vipps\Payment\Gateway\Validator
 */
class OrderValidator extends AbstractValidator
{
    /**
     * @param array $validationSubject
     * @return Result
     */
    public function validate(array $validationSubject)
    {
        $orderId = isset($validationSubject['jsonData']['orderId']) ? $validationSubject['jsonData']['orderId'] : null;

        $isValid = (bool)$orderId;

        $payment = $this->subjectReader->readPayment($validationSubject);
        if ($payment) {
            $orderAdapter = $payment->getOrder();
            $isValid = ($orderId == $orderAdapter->getOrderIncrementId());
        }

        $errorMessages = $isValid ? [] : [__('Gateway response error. Order Id is incorrect')];

        return $this->createResult($isValid, $errorMessages);
    }
}
