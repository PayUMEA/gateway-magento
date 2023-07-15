<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Http\Client;

use PayU\Api\ResponseInterface;

/**
 * class TransactionVoid
 * @package PayU\Gateway\Gateway\Http\Client
 */
class TransactionVoid extends AbstractTransaction
{
    /**
     * @inheritdoc
     */
    protected function process(array $data): ResponseInterface
    {
        $storeId = (int)$data['store_id'] ?? null;
        // not sending store id
        unset($data['store_id']);

        return $this->adapterFactory->create($storeId)->void($data);
    }
}
