<?php
/**
 * Copyright 2018 Vipps
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

namespace Vipps\Payment\Model\Quote;

use Vipps\Payment\Model\Adapter\Quote\AttemptFactory;

/**
 * Attempt Management.
 */
class AttemptManagement
{
    /**
     * AttemptManagement constructor.
     *
     */
    public function __construct()
    {
        $this->attemptFactory = new AttemptFactory();
    }

    /**
     * Create new saved attempt. Increment attempt count. Fill it with message later.
     *
     * @param \Vipps_Payment_Model_Quote $quote
     * @param bool $ignoreIncrement
     * @return \Vipps_Payment_Model_Quote_Attempt
     * @throws \Exception
     */
    public function createAttempt(\Vipps_Payment_Model_Quote $quote, $ignoreIncrement = false)
    {
        /** @var \Vipps_Payment_Model_Quote_Attempt $attempt */
        $attempt = $this
            ->attemptFactory
            ->create(['parent_id' => $quote->getId()])
            ->setDataChanges(true);

        // Saving attempt right immediately after creation cause it's already happened.
        $attempt->save();

        if (!$ignoreIncrement) {
            // Increase attempt counter.
            $quote->incrementAttempt();
            $quote->save();
        }

        return $attempt;
    }

    /**
     * @param \Vipps_Payment_Model_Quote_Attempt $attempt
     * @return \Mage_Core_Model_Abstract
     * @throws \Exception
     */
    public function save(\Vipps_Payment_Model_Quote_Attempt $attempt)
    {
        return $attempt->save();
    }
}
