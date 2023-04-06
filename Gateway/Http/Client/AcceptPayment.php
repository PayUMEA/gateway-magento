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
use PayU\Gateway\Model\Adapter\PayUAdapterFactory;
use PayU\Gateway\Model\Payment\Operations\AcceptPaymentOperation;
use PayU\Gateway\Model\Payment\TransferObjectFactory;
use Psr\Log\LoggerInterface;

/**
 * class AcceptPayment
 * @package PayU\Gateway\Gateway\Http\Client
 */
class AcceptPayment extends AbstractTransaction
{
    /**
     * @param LoggerInterface $logger
     * @param Logger $customLogger
     * @param PayUAdapterFactory $adapterFactory
     * @param AcceptPaymentOperation $acceptOperation
     * @param TransferObjectFactory $transferFactory
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected Logger $customLogger,
        protected PayUAdapterFactory $adapterFactory,
        protected AcceptPaymentOperation $acceptOperation,
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
        $storeId = (int)$data['store_id'] ?? null;
        $transactionInfo = $payment->getTransactionAdditionalInfo()
            ? $payment->getTransactionAdditionalInfo()['transactionInfo']
            : null;

        if (!$transactionInfo) {
            $transactionInfo = $this->adapterFactory->create($storeId)->transactionInfo($data);
            $transferObject = $this->transferFactory->create(['data' => $transactionInfo->toArray()]);

            $transferObject->importTransactionInfo($payment);
            $transactionInfo = $transferObject;
        }

        try {
            $this->acceptOperation->accept($payment);
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }

        return $transactionInfo;
    }
}
