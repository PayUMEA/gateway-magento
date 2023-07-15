<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment\Operations;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use PayU\Gateway\Gateway\Config\Config;

/**
 * class TransactionUpdateOperation
 * @package PayU\Gateway\Model\Payment\Operations
 */
class TransactionUpdateOperation
{
    /**
     * @param Config $config
     * @param BuilderInterface $transactionBuilder
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        protected readonly Config $config,
        protected readonly BuilderInterface $transactionBuilder,
        protected OrderPaymentRepositoryInterface $paymentRepository,
        protected TransactionRepositoryInterface $transactionRepository
    ) {
    }

    /**
     * @param Order $order
     * @param InfoInterface $payment
     * @param DataObject $transactionInfo
     * @return void
     * @throws LocalizedException
     */
    public function update(
        OrderInterface $order,
        InfoInterface $payment,
        DataObject $transactionInfo
    ): void {
        $transactionBuilder = $this->transactionBuilder->setPayment($payment);
        $transactionBuilder->setOrder($order);
        $transactionBuilder->setFailSafe(true);
        $transactionBuilder->setTransactionId($transactionInfo->getPayUReference());

        $formattedPrice = $order->getBaseCurrency()->formatTxt(
            $order->getGrandTotal()
        );
        $message = __('The transaction amount is %1.', $formattedPrice);

        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        $transactionBuilder->setMessage($message);
        $transaction = $transactionBuilder->build($this->getPaymentAction((int)$order->getStoreId()));
        $transaction->setAdditionalInformation(
            Order\Payment\Transaction::RAW_DETAILS,
            $transaction->getAdditionalInformation(
                Order\Payment\Transaction::RAW_DETAILS
            ) + $transactionInfo->getPaymentData()
        );
        $payment->addTransactionCommentsToOrder(
            $transaction,
            $message
        );

        $this->paymentRepository->save($payment);
        $this->transactionRepository->save($transaction);
    }

    /**
     * @param int $storeId
     * @return string
     */
    private function getPaymentAction(int $storeId): string
    {
        $transactionType = $this->config->getTransactionType($storeId);

        return match ($transactionType) {
            'order',  => TransactionInterface::TYPE_ORDER,
            'authorize' => TransactionInterface::TYPE_AUTH,
            'authorize_capture' => TransactionInterface::TYPE_CAPTURE
        };
    }
}
