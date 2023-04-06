<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment;

use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\OrderFactory;
use PayU\Gateway\Model\Constants\TransactionState;
use PayU\Gateway\Model\Payment\Operations\CreateInvoiceOperation;
use PayU\Gateway\Model\Payment\Operations\TransactionUpdateOperation;
use PayU\Gateway\Model\Payment\Operations\ProcessFraudOperation;
use Psr\Log\LoggerInterface;

/**
 * class AbstractOperation
 * @package PayU\Gateway\Model\Payment
 */
abstract class AbstractOperation
{
    /**
     * @param Validator $validator
     * @param OrderFactory $orderFactory
     * @param CreateInvoiceOperation $invoiceOperation
     * @param ProcessFraudOperation $fraudOperation
     * @param TransactionUpdateOperation $transactionOperation
     * @param LoggerInterface $logger
     * @param BuilderInterface $transactionBuilder
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        protected readonly Validator $validator,
        protected readonly OrderFactory $orderFactory,
        protected readonly CreateInvoiceOperation $invoiceOperation,
        protected readonly TransactionUpdateOperation $transactionOperation,
        protected readonly ProcessFraudOperation $fraudOperation,
        protected readonly LoggerInterface $logger,
        protected readonly BuilderInterface $transactionBuilder,
        protected OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * @param InfoInterface $payment
     * @param Order $order
     * @param DataObject $transactionInfo
     */
    protected function addStatusCommentOnUpdate(InfoInterface $payment, Order $order, DataObject $transactionInfo): void
    {
        $transactionId = $transactionInfo->getTranxId();

        if ($payment->getIsTransactionApproved()) {
            $message = __(
                'Transaction %1 has been approved. Amount %2. Transaction status is "%3"',
                $transactionId,
                $payment->getOrder()->getBaseCurrency()->formatTxt($payment->getAmountOrdered()),
                $transactionInfo->getTransactionState()
            );
            $order->addCommentToStatusHistory($message);
        } elseif ($payment->getIsTransactionPending()) {
            $message = __(
                'Transaction %1 is pending payment. Amount %2. Transaction status is "%3"',
                $transactionId,
                $payment->getOrder()->getBaseCurrency()->formatTxt($payment->getAmountOrdered()),
                $transactionInfo->getTransactionState()
            );
            $order->addCommentToStatusHistory($message);
        } elseif ($payment->getIsTransactionDenied()) {
            $message = __(
                'Transaction %1 has been voided/declined. Transaction status is "%2". Amount %3.',
                $transactionId,
                $transactionInfo->getTransactionState(),
                $payment->getOrder()->getBaseCurrency()->formatTxt($payment->getAmountOrdered())
            );
            $order->addCommentToStatusHistory($message);
        }
    }

    /**
     * Fill payment with credit card data from response from PayU.
     *
     * @param InfoInterface $payment
     * @param DataObject $transactionInfo
     * @return void
     */
    protected function updatePayment(InfoInterface $payment, DataObject $transactionInfo): void
    {
        $payment->setLastTransId($transactionInfo->getTranxId())
            ->setTransactionId($transactionInfo->getTranxId())
            ->setParentTransactionId(null)
            ->setIsTransactionClosed(true)
            ->setAdditionalInformation(
                [Order\Payment\Transaction::RAW_DETAILS => $transactionInfo->toArray()]
            )
            ->setTransactionAdditionalInfo(TransactionState::REAL_TRANSACTION_ID_KEY->value, $transactionInfo->getTranxId());

        if ($transactionInfo->hasCreditCard()) {
            $payment->setGatewayReference($transactionInfo->getGatewayReference())
                ->setCcLast4($payment->encrypt(substr($transactionInfo->getCreditCardNumber(), -4)));
        }

        if ($transactionInfo->getTransactionState() == TransactionState::AWAITING_PAYMENT->value) {
            $payment->setIsTransactionPending(true);
        }

        if ($transactionInfo->isFraudDetected()) {
            $payment->setIsFraudDetected(true);
        }
    }
}
