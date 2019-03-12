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

class Vipps_Payment_Block_Adminhtml_RequestProfiling_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Vipps_Payment_Block_Adminhtml_QuoteMonitoring_Grid constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setDefaultSort('id');
        $this->setId('vipps_requestprofiling_grid');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(false);
    }

    /**
     * @param $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', ['entity_id' => $row->getId()]);
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return string
     */
    protected function _getCollectionClass()
    {
        return 'vipps_payment/profiling_item_collection';
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Grid
     * @throws Exception
     */
    protected function _prepareColumns()
    {

        $this->addColumn('entity_id',
            array(
                'header' => $this->__('ID'),
                'align'  => 'right',
                'width'  => '50px',
                'index'  => 'entity_id'
            )
        );

        $this->addColumn('increment_id',
            array(
                'header'   => $this->__('Order Increment Id'),
                'index'    => 'increment_id',
                'type'     => 'text',
                'sortable' => true,
                'filter'   => 0
            )
        );

        $this->addColumn('status_code',
            array(
                'header' => $this->__('Status Code'),
                'index'  => 'status_code',
                'type'   => 'number',
                'filter' => 0
            )
        );

        $this->addColumn('request_type',
            array(
                'header'  => $this->__('Request Type'),
                'index'   => 'request_type',
                'type'    => 'options',
                'options' => Mage::getResourceSingleton('vipps_payment/profiling_item_requestType')->toOptionHash(),

            )
        );


        $this->addColumn('created_at',
            array(
                'header'   => $this->__('Created At'),
                'index'    => 'created_at',
                'type'     => 'datetime',
                'filter'   => 0,
                'sortable' => true
            )
        );

        $this->addColumn('action',
            array(
                'header'  => $this->__('Action'),
                'index'   => 'action',
                'type'    => 'action',
                'actions' => array(
                    array(
                        'url'     => $this->getUrl('*/*/view', ['entity_id' => '$entity_id']),
                        'caption' => $this->__('View')
                    ),
                )
            )
        );

        return parent::_prepareColumns();
    }
}
