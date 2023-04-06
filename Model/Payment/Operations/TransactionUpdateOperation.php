<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment\Operations;

use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

/**
 * class TransactionUpdateOperation
 * @package PayU\Gateway\Model\Payment\Operations
 */
class TransactionUpdateOperation
{
    /**
     * @param BuilderInterface $transactionBuilder
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
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
     */
    public function update(
        Order $order,
        InfoInterface $payment,
        DataObject $transactionInfo
    ): void {
        $transactionBuilder = $this->transactionBuilder->setPayment($payment);
        $transactionBuilder->setOrder($order);
        $transactionBuilder->setFailSafe(true);
        $transactionBuilder->setTransactionId($payment->getTransactionId());
        $transactionBuilder->setAdditionalInformation(
            [
                Order\Payment\Transaction::RAW_DETAILS => $transactionInfo->getPaymentData()
            ]
        );

        $formattedPrice = $order->getBaseCurrency()->formatTxt(
            $order->getGrandTotal()
        );
        $message = __('The transaction amount is %1.', $formattedPrice);

        $transaction = $transactionBuilder->build(TransactionInterface::TYPE_ORDER);
        $payment->addTransactionCommentsToOrder(
            $transaction,
            $message
        );
        $this->paymentRepository->save($payment);
        $this->transactionRepository->save($transaction);
    }
}
