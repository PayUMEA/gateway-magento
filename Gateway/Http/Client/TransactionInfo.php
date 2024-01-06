<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use PayUSdk\Api\ResponseInterface;
use PayU\Gateway\Gateway\Request\StoreConfigBuilder;

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
        $storeId = (int)$data[StoreConfigBuilder::STORE_ID] ?? null;
        // not sending store id
        unset($data[StoreConfigBuilder::STORE_ID]);

        return $this->adapterFactory->create(
            $storeId,
            $data[StoreConfigBuilder::METHOD_CODE]
        )->transactionInfo($data);
    }
}
