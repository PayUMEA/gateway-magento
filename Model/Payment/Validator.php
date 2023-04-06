<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use PayU\Gateway\Helper\DataFactory;
use PayU\Gateway\Model\Constants\TransactionState;
use Psr\Log\LoggerInterface;

/**
 * class Validator
 * @package PayU\Gateway\Model\Payment\Operations
 */
class Validator
{
    /**
     * @param LoggerInterface $logger
     * @param DataFactory $dataFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly DataFactory $dataFactory,
        protected readonly OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * @param Order $order
     * @param DataObject $transactionInfo
     * @return void
     * @throws LocalizedException
     */
    public function validate(Order $order, DataObject $transactionInfo): void
    {
        $payment = $order->getPayment();

        try {
            $this->transactionIdValid($transactionInfo);
            $this->transactionStateValid($transactionInfo);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());

            throw $e;
        }

        // Here we must do something to check for a Pending request
        if ($payment->getIsTransactionPending()) {
            $this->orderRepository->save($order);

            return;
        }

        // Amounts should be equal for capturing order.
        if (!$this->amountValid($transactionInfo->getTotalCaptured(), (float)$payment->getBaseAmountOrdered())) {
            $message = __(
                'Something went wrong: the paid amount does not match the order amount.'
                . ' Please correct this and try again.'
            );

            throw new LocalizedException($message);
        }
    }

    /**
     * @param DataObject $transactionInfo
     * @return void
     * @throws LocalizedException
     */
    protected function transactionStateValid(DataObject $transactionInfo): void
    {
        $state = $transactionInfo->getTransactionState();

        switch ($state) {
            case TransactionState::SUCCESSFUL->value:
            case TransactionState::PROCESSING->value:
            case TransactionState::AWAITING_PAYMENT->value:
                break;
            case TransactionState::FAILED->value:
            case TransactionState::EXPIRED->value:
            case TransactionState::TIMEOUT->value:
                throw new LocalizedException(
                    $this->dataFactory->create()->wrapGatewayError($transactionInfo->getResultMessage())
                );
            default:
                throw new LocalizedException(
                    __('There was a payment validation error.')
                );
        }
    }

    /**
     * @param DataObject $transactionInfo
     * @return void
     * @throws LocalizedException
     */
    protected function transactionIdValid(DataObject $transactionInfo): void
    {
        if (!$transactionInfo->getTranxId()) {
            throw new LocalizedException(
                __('Payment validation error: invalid PayU reference')
            );
        }
    }

    /**
     * @param float $amountPaid
     * @param float $amountOrdered
     * @return bool
     */
    protected function amountValid(float $amountPaid, float $amountOrdered): bool
    {
        return sprintf('%.2F', $amountOrdered) == sprintf('%.2F', $amountPaid);
    }
}
