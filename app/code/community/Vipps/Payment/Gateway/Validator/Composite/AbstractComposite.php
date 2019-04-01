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
 * Class ValidatorComposite
 */
class Vipps_Payment_Gateway_Validator_Composite_AbstractComposite extends Vipps_Payment_Gateway_Validator_AbstractValidator
{
    /**
     * @var \Vipps_Payment_Model_Helper_Pool
     */
    private $validatorsPool;

    /**
     * @param array $validators
     */
    public function __construct(
        array $validators = []
    ) {
        parent::__construct();
        $this->validatorsPool = new Vipps_Payment_Model_Helper_Pool($validators);
    }

    /**
     * Performs domain level validation for business object
     *
     * @param array $validationSubject
     * @return Vipps_Payment_Gateway_Validator_Result
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
