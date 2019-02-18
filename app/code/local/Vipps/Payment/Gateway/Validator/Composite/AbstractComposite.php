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

namespace Vipps\Payment\Gateway\Validator\Composite;

use Vipps\Payment\Gateway\Validator\AbstractValidator;
use Vipps\Payment\Lib\Pool;

/**
 * Class ValidatorComposite
 * @package Magento\Payment\Gateway\Validator
 * @api
 * @since 100.0.2
 */
class AbstractComposite extends AbstractValidator
{
    /**
     * @var Pool
     */
    private $validatorsPool;

    /**
     * @param array $validators
     */
    public function __construct(
        array $validators = []
    ) {
        $this->validatorsPool = new Pool($validators);
    }

    /**
     * Performs domain level validation for business object
     *
     * @param array $validationSubject
     * @return \Vipps\Payment\Gateway\Validator\Result
     */
    public function validate(array $validationSubject)
    {
        $isValid = true;
        $failsDescriptionAggregate = [];
        foreach ($this->validatorsPool->getAll() as $validator) {
            $result = $validator->validate($validationSubject);
            if (!$result->isValid()) {
                $isValid = false;
                $failsDescriptionAggregate = array_merge(
                    $failsDescriptionAggregate,
                    $result->getFailsDescription()
                );
            }
        }

        return $this->createResult($isValid, $failsDescriptionAggregate);
    }
}
