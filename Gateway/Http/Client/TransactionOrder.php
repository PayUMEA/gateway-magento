<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Http\Client;

use PayU\Resource;

/**
 * class TransactionOrder
 * @package PayU\Gateway\Gateway\Http\Client
 */
class TransactionOrder extends AbstractTransaction
{
    /**
     * @inheritdoc
     */
    protected function process(array $data): Resource
    {
        $storeId = (int)$data['store_id'] ?? null;
        // not sending store id
        unset($data['store_id']);

        return $this->adapterFactory->create($storeId)->order($data);
    }
}
