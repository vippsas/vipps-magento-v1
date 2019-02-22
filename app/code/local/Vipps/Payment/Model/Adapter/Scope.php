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

namespace Vipps\Payment\Model\Adapter;

/**
 * Class Scope
 */
class Scope
{
    /**
     * @const string
     */
    const SCOPE_STOREGROUP = 'storegrp'; // 8 symbols table structure limit.

    /**
     * @return int
     * @throws \Mage_Core_Exception
     */
    public function getId()
    {
        return $this->getGroup()->getId();
    }

    /**
     * @return \Mage_Core_Model_Store_Group
     * @throws \Mage_Core_Exception
     */
    private function getGroup()
    {
        return \Mage::app()->getGroup();
    }

    /**
     * @return string
     */
    public function getScopeType()
    {
        return self::SCOPE_STOREGROUP;
    }
}
