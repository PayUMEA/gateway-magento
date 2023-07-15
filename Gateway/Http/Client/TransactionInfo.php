<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use PayU\Api\ResponseInterface;

/**
 * class TransactionInfo
 * @package PayU\Gateway\Gateway\Http\Client
 */
class TransactionInfo extends AbstractTransaction
{
    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    protected function process(array $data): ResponseInterface
    {
        $storeId = (int)$data['store_id'] ?? null;
        // not sending store id
        unset($data['store_id']);

        return $this->adapterFactory->create($storeId)->transactionInfo($data);
    }
}
