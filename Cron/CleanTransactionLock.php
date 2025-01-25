<?php
/**
 * Copyright © 2024 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PayU\Gateway\Model\ResourceModel\Transaction\Collection;
use PayU\Gateway\Model\ResourceModel\Transaction\CollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * class CleanTransactionLock
 * @package PayU\Gateway\Cron
 */
class CleanTransactionLock
{
    private const CRON_CONFIG_PATTERN = 'payu_gateway/txn_lock/%s';

    /**
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function execute()
    {
        $processId = uniqid();
        $this->logger->info("PAYU GATEWAY TXN LOCK CRON: Started, PID: $processId");

        if (!$this->getConfigValue('enable')) {
            $this->logger->info("PAYU GATEWAY TXN LOCK CRON: disabled, PID: $processId");

            return;
        }

        $locks = $this->getLockCollection();

        $this->logger->info("PAYU GATEWAY TXN LOCK CRON: ($processId)" . count($locks) . ' locks to be cleaned.');

        foreach ($locks->getItems() as $lock) {
            $lock->setId($lock->getEntityId());
            $lock->delete();
        }

        $this->logger->info("PAYU GATEWAY TXN STATUS CRON: Ended, PID: $processId");
    }

    /**
     * @return Collection
     */
    public function getLockCollection(): Collection
    {
        return $this->collectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter(
                'created_at',
                [
                    ['lteq' => $this->daysFilter()],
                    ['null' => true]
                ]
            );
    }

    /**
     * @return false|string
     */
    protected function daysFilter()
    {
        $days = (int)$this->getConfigValue('keep_log');
        $to = date("Y-m-d h:i:s");
        $from = strtotime("-$days days", strtotime($to));

        return date('Y-m-d h:i:s', $from);
    }

    /**
     * @param string $field
     * @param ?string $storeId
     * @return mixed
     */
    protected function getConfigValue(string $field, ?string $storeId = null)
    {
        $path = sprintf(self::CRON_CONFIG_PATTERN, $field);

        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
