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

$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Setup */

$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('vipps_payment/jwt'))
    ->addColumn(
        'token_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
        'Token ID'
    )->addColumn(
        'scope',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        8,
        ['nullable' => false, 'default' => 'default'],
        'Scope'
    )->addColumn(
        'scope_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        ['nullable' => false, 'default' => '0'],
        'Config Scope Id'
    )->addColumn(
        'token_type',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        16,
        ['nullable' => false, 'default' => 'Bearer'],
        'Token Type (default Bearer)'
    )->addColumn(
        'expires_in',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [],
        'Token expiry duration in seconds'
    )->addColumn(
        'ext_expires_in',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        ['nullable' => false, 'default' => 0],
        'Any extra expiry time. This is zero only'
    )->addColumn(
        'expires_on',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [],
        'Token expiry time in epoch time format'
    )->addColumn(
        'not_before',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [],
        'Token creation time in epoch time format'
    )->addColumn(
        'resource',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        255,
        [],
        'A common resource object that comes by default. Not used in token validation'
    )->addColumn(
        'access_token',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        '8k',
        ['nullable' => false],
        'The actual access token that needs to be used in request header'
    )->setComment(
        'JWT access token'
    );

$installer->getConnection()->createTable($table);


$table = $installer->getConnection()
    ->newTable(
        $installer->getTable('vipps_payment/profiling')
    )->addColumn(
        'entity_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
        'Entity Id'
    )->addColumn(
        'increment_id',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        32,
        [],
        'Increment Id'
    )->addColumn(
        'status_code',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        4,
        [],
        'Status Code'
    )->addColumn(
        'request_type',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        50,
        [],
        'Request Type'
    )->addColumn(
        'request',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        '64k',
        [],
        'Request'
    )->addColumn(
        'response',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        '64k',
        [],
        'Response'
    )->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        ['nullable' => false, 'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT],
        'Created At'
    );
$installer->getConnection()->createTable($table);


$table = $installer->getConnection()->newTable($installer->getTable('vipps_payment/quote'))
    ->addColumn(
        'entity_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
        'Entity Id'
    )->addColumn(
        'quote_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        ['nullable' => true, 'unsigned' => true],
        'Quote Id'
    )->addColumn(
        'store_id',
        Varien_Db_Ddl_Table::TYPE_SMALLINT,
        5,
        ['nullable' => false, 'default' => '0'],
        'Store ID'
    )->addColumn(
        'reserved_order_id',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        32,
        ['nullable' => false, 'default' => ''],
        'Order Increment Id'
    )->addColumn(
        'status',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        20,
        ['nullable' => false, 'default' => 'new'],
        'Status'
    )->addColumn(
        'attempts',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        3,
        ['nullable' => false, 'default' => '0'],
        'Attempts Number'
    )
    ->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        ['default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT, 'nullable' => false],
        'Created at'
    )->addColumn(
        'updated_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        ['default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT_UPDATE, 'nullable' => false],
        'Updated at'
    )
    ->addIndex($installer->getIdxName('vipps_payment/quote', 'quote_id'), 'quote_id')
    ->addForeignKey(
        $installer->getFkName('vipps_payment/quote', 'quote_id', 'sales/quote', 'entity_id'),
        'quote_id',
        $installer->getTable('sales/quote'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_SET_NULL
    );

$installer->getConnection()->createTable($table);


$table = $installer->getConnection()->newTable($installer->getTable('vipps_payment/quote_attempt'))
    ->addColumn(
        'entity_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
        'Entity Id'
    )->addColumn(
        'parent_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        ['nullable' => false, 'unsigned' => true],
        'Vipps Quote Id'
    )->addColumn(
        'message',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        null,
        [],
        'Message'
    )->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        ['nullable' => false, 'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT],
        'Created at'
    )
    ->addIndex($installer->getIdxName('vipps_payment/quote_attempt', 'parent_id'), 'parent_id')
    ->addForeignKey(
        $installer->getFkName('vipps_payment/quote_attempt', 'parent_id', 'vipps_payment/quote', 'entity_id'),
        'parent_id',
        $installer->getTable('vipps_payment/quote'),
        'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE
    );

$installer->getConnection()->createTable($table);
