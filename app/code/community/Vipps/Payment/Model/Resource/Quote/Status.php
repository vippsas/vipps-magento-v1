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
 * Class Vipps_Payment_Model_Resource_Quote_Status
 */
class Vipps_Payment_Model_Resource_Quote_Status
{
    public function toOptionHash()
    {
        return [
            Vipps_Payment_Model_QuoteStatusInterface::STATUS_NEW           => __('New'),
            Vipps_Payment_Model_QuoteStatusInterface::STATUS_PROCESSING    => __('Processing'),
            Vipps_Payment_Model_QuoteStatusInterface::STATUS_EXPIRED       => __('Expired'),
            Vipps_Payment_Model_QuoteStatusInterface::STATUS_PLACED        => __('Placed'),
            Vipps_Payment_Model_QuoteStatusInterface::STATUS_PLACE_FAILED  => __('Place Failed'),
            Vipps_Payment_Model_QuoteStatusInterface::STATUS_CANCELED      => __('Canceled'),
            Vipps_Payment_Model_QuoteStatusInterface::STATUS_CANCEL_FAILED => __('Cancel Failed')
        ];
    }

    /**
     * @param string $code
     * @return string
     */
    public function getLabel($code)
    {
        $options = $this->toOptionHash();

        return isset($options[$code]) ? $options[$code] : $code;
    }
}
