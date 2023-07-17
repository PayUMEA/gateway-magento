<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Http\Client;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Logger;
use PayU\Gateway\Gateway\Request\StoreConfigBuilder;
use PayU\Gateway\Model\Adapter\PayUAdapterFactory;
use PayU\Gateway\Model\Constants\TransactionType;
use PayU\Gateway\Model\Payment\Operations\DenyPaymentOperation;
use PayU\Gateway\Model\Payment\TransferObject;
use PayU\Gateway\Model\Payment\TransferObjectFactory;
use Psr\Log\LoggerInterface;

/**
 * class DenyPayment
 * @package PayU\Gateway\Gateway\Http\Client
 */
class DenyPayment extends AbstractTransaction
{
    /**
     * @param LoggerInterface $logger
     * @param Logger $customLogger
     * @param PayUAdapterFactory $adapterFactory
     * @param DenyPaymentOperation $denyOperation
     * @param TransferObjectFactory $transferFactory
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected Logger $customLogger,
        protected PayUAdapterFactory $adapterFactory,
        protected DenyPaymentOperation $denyOperation,
        private readonly TransferObjectFactory $transferFactory
    ) {
        parent::__construct($logger, $customLogger, $adapterFactory);
    }

    /**
     * @param array $data
     * @return DataObject
     * @throws LocalizedException
     */
    protected function process(array $data): DataObject
    {
        $payment = $data['payment'];
        $storeId = (int)$data[StoreConfigBuilder::STORE_ID] ?? null;
        $transactionInfo = $payment->getTransactionAdditionalInfo()
            ? $payment->getTransactionAdditionalInfo()['transactionInfo']
            : null;

        if (!$transactionInfo) {
            /** @var TransferObject $transactionInfo */
            $transactionInfo = $this->adapterFactory->create(
                $storeId,
                $data[StoreConfigBuilder::METHOD_CODE]
            )->transactionInfo($data);
            $transferObject = $this->transferFactory->create(['data' => $transactionInfo->toArray()]);

            $transferObject->importTransactionInfo($payment);
            $transactionInfo = $transferObject;
        }

        try {
            if (
                $transactionInfo->getTranxId()
                && strtoupper($transactionInfo->getTransactionType()) == TransactionType::PAYMENT->value
            ) {
                $this->denyOperation->deny($payment);
            }
        } catch (Exception $exception) {
            $this->logger->critical($exception);

            throw $exception;
        }

        return $transactionInfo;
    }
}
