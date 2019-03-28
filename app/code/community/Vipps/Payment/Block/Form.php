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

class Vipps_Payment_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * @return string
     */
    public function getMethodLabelAfterHtml()
    {
        $methodTitle = $this->getMethod()->getTitle();
        $vippsNote = __('You will be redirected to the Vipps website.');
        $imageUrl = $this->getSkinUrl('images/vippspayment/vipps_logo_rgb.png');

        return <<<HTML
            <span class="vipps-payment-label">
                <img width="170" src="{$imageUrl}" class="payment-icon" alt="{$methodTitle}"/>
                <span class="clearfix"></span>
                <span class="vipps-payment-method-note">{$vippsNote}</span>
            </span>
HTML;

    }

    /**
     * @return bool
     */
    public function hasMethodTitle()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getMethodTitle()
    {
        return '';
    }
}
