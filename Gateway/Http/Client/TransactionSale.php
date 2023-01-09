<?php declare(strict_types=1);
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace PayU\Gateway\Gateway\Http\Client;

/**
 * class TransactionSale
 * @package PayU\Gateway\Gateway\Http\Client
 */
class TransactionSale extends AbstractTransaction
{
    /**
     * @inheritdoc
     */
    protected function process(array $data): mixed
    {
        $storeId = $data['store_id'] ?? null;
        // not sending store id
        unset($data['store_id']);

        return $this->adapterFactory->create($storeId)->sale($data);
    }
}
