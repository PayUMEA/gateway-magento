<?php
/**
 * Copyright © 2024 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\ResourceModel\Transaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use PayU\Gateway\Model\Lock\Transaction;

/**
 * class Collection
 * @package PayU\Gateway\Model\ResourceModel\Transaction
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'payu_gateway_transaction_collection';
    protected $_eventObject = 'transaction_collection';

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Transaction::class,
            \PayU\Gateway\Model\ResourceModel\Transaction::class
        );
        $this->_map['fields']['entity_id'] = 'main_table.entity_id';
    }
}
