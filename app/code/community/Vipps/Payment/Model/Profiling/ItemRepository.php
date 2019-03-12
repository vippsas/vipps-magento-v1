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

/**
 * Class ItemRepository
 */
class Vipps_Payment_Model_Profiling_ItemRepository
{
    /**
     * @param Varien_Object $itemDo
     * @return Mage_Core_Model_Abstract|
     * @throws Mage_Core_Exception
     */
    public function save(Varien_Object $itemDo)
    {
        try {
            /** @var Vipps_Payment_Model_Profiling_Item $item */
            $item = Mage::getModel('vipps_payment/profiling_item')
                ->setData($itemDo->getData())
                ->save();
        } catch (\Exception $exception) {
            throw new Mage_Core_Exception(__($exception->getMessage()));
        }
        return $item;
    }

    /**
     * @param int $itemId
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function deleteById($itemId)
    {
        return $this->delete($this->get($itemId));
    }

    /**
     * @param \Vipps_Payment_Model_Profiling_Item $item
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function delete(\Vipps_Payment_Model_Profiling_Item $item)
    {
        try {
            $item->delete();
        } catch (\Exception $exception) {
            throw new Mage_Core_Exception(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @param $itemId
     *
     * @return false|Mage_Core_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function get($itemId)
    {
        $item = $this->itemFactory->create();
        $item->load($itemId);
        if (!$item->getId()) {
            throw new Mage_Core_Exception(__('Profiling item with id "%s" does not exist.', $itemId));
        }
        return $item;
    }
}
