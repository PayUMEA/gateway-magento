<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Http\Client;

use PayUSdk\Api\ResponseInterface;
use PayU\Gateway\Gateway\Request\StoreConfigBuilder;

/**
 * class TransactionCapture
 * @package PayU\Gateway\Gateway\Http\Client
 */
class TransactionCapture extends AbstractTransaction
{
    /**
     * @param array $data
     * @return ResponseInterface
     */
    protected function process(array $data): ResponseInterface
    {
        $storeId = (int)$data[StoreConfigBuilder::STORE_ID] ?? null;
        // not sending store id
        unset($data[StoreConfigBuilder::STORE_ID]);

        return $this->adapterFactory->create(
            $storeId,
            $data[StoreConfigBuilder::METHOD_CODE]
        )->capture($data);
    }
}
