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
namespace Vipps\Payment\Model\Adapter\Profiling;

use Vipps\Payment\Model\Adapter\ResourceModel\Profiling\Item;
use Vipps\Payment\Model\Adapter\ResourceModel\Profiling\Item as ItemResource;
use Vipps\Payment\Model\Adapter\ResourceModel\Profiling\Item\CollectionFactory;

/**
 * Class ItemRepository
 * @package Vipps\Payment\Model\Profiling
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemRepository
{
    /**
     * @var ItemResource
     */
    private $resource;

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * ItemRepository constructor.
     *
     */
    public function __construct() {
        $this->resource = new \Vipps\Payment\Model\Adapter\Adapter\Resource();
        $this->itemFactory = new ItemFactory();
    }

    /**
     * @param ItemInterface $item
     *
     * @return Item
     * @throws CouldNotSaveException
     */
    public function save(\Varien_Object $item)
    {
        try {
            $item = $this->itemFactory->create($item->getData());
            $this->resource->save($item);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $item;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return \Magento\Eav\Api\Data\AttributeGroupSearchResultsInterface|ItemSearchResultsInterface
     */
//    public function getList(SearchCriteriaInterface $searchCriteria)
//    {
//        /** @var ItemSearchResultsInterface $searchResults */
//        $searchResults = $this->searchResultsFactory->create();
//        $searchResults->setSearchCriteria($searchCriteria);
//
//        /** @var Collection $collection */
//        $collection = $this->itemFactory->create()->getCollection();
//
//        $searchResults->setTotalCount($collection->getSize());
//        $sortOrders = $searchCriteria->getSortOrders();
//        if ($sortOrders) {
//            /** @var SortOrder $sortOrder */
//            foreach ($searchCriteria->getSortOrders() as $sortOrder) {
//                $collection->addOrder(
//                    $sortOrder->getField(),
//                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
//                );
//            }
//        }
//        $collection->setCurPage($searchCriteria->getCurrentPage());
//        $collection->setPageSize($searchCriteria->getPageSize());
//        $items = [];
//        /** @var Item $itemModel */
//        foreach ($collection as $itemModel) {
//            $itemDataObject = $this->itemFactory->create($itemModel->getData());
//            $items[] = $itemDataObject;
//        }
//        $searchResults->setItems($items);
//        return $searchResults;
//    }

    /**
     * @param $itemId
     *
     * @return false|\Mage_Core_Model_Abstract
     */
    public function get($itemId)
    {
        $item = $this->itemFactory->create();
        $this->resource->load($item, $itemId);
        if (!$item->getId()) {
            throw new NoSuchEntityException(__('Profiling item with id "%1" does not exist.', $itemId));
        }
        return $item;
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public function delete(Item $item)
    {
        try {
            $this->resource->delete($item);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @param int $itemId
     *
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($itemId)
    {
        return $this->delete($this->get($itemId));
    }
}
