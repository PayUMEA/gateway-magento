<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use PayU\Gateway\Model\Constants\ResultCode;
use PayU\Gateway\Model\Constants\TransactionState;
use PayU\Gateway\Model\Payment\Operations\HandleInstantPaymentNotification;

/**
 * class Processor
 * @package PayU\Gateway\Model\Payment
 */
class Processor
{
    /**
     * @param HandleInstantPaymentNotification $ipnOperation
     */
    public function __construct(
        private readonly HandleInstantPaymentNotification $ipnOperation
    ) {
    }

    /**
     * Process return from PayU after payment
     *
     * @param OrderInterface $order
     * @param string $payUReference
     * @return array
     * @throws LocalizedException
     */
    public function response(OrderInterface $order, string $payUReference): array
    {
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $method->fetchTransactionInfo($payment, $payUReference);

        if ($payment->getIsTransactionApproved() ||
            $payment->getIsTransactionProcessing() ||
            $payment->getIsTransactionPending() // EFT awaiting payment
        ) {
            $method->acceptPayment($payment);

            return [true, ''];
        }

        $transactionAdditionalInfo = $payment->getTransactionAdditionalInfo();
        /** @var DataObject $transactionInfo */
        $transactionInfo = $transactionAdditionalInfo['transactionInfo'] ?? null;

        $method->denyPayment($payment);

        return [false, $transactionInfo ? $transactionInfo->getDisplayMessage() : 'Payment confirmation failed.'];
    }

    /**
     * @param OrderInterface $order
     * @param array $ipnData
     * @return void
     * @throws LocalizedException
     */
    public function notify(OrderInterface $order, array $ipnData): void
    {
        if ($order->getState() === strtolower(TransactionState::PROCESSING->value)) {
            return;
        }

        $payUReference = $ipnData['PayUReference'];
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $method->fetchTransactionInfo($payment, $payUReference);

        $transactionAdditionalInfo = $payment->getTransactionAdditionalInfo();
        /** @var DataObject $transactionInfo */
        $transactionInfo = $transactionAdditionalInfo['transactionInfo'] ?? null;

        $resultCode = $transactionInfo ? $transactionInfo->getResultCode() : 'N/A';

        if (isset($resultCode) && in_array($resultCode, array_column(ResultCode::cases(), 'value'))) {
            $comment = "<strong>-----PAYU NOTIFICATION RECEIVED---</strong><br />";
            $comment .= '<strong>Payment unsuccessful: </strong><br />';
            $comment .= "PayU Reference: " . $transactionInfo->getTranxId() . "<br />";
            $comment .= "Point Of Failure: " . $transactionInfo->getPointOfFailure() . "<br />";
            $comment .= "Result Code: " . $transactionInfo->getResultCode();
            $comment .= "Result Message: " . $transactionInfo->getResultMessage();
            $order->addCommentToStatusHistory($comment, true);
            $order->cancel();

            return;
        }

        $this->ipnOperation->notify($order, $payment, $transactionInfo);
    }

    /**
     * @param OrderInterface $order
     * @param string $payUReference
     * @return void
     * @throws LocalizedException
     */
    public function cancel(OrderInterface $order, string $payUReference): void
    {
        $payment = $order->getPayment();
        $payment->setAdditionalInformation('payUReference', $payUReference);
        $payment->getMethodInstance()->cancel($payment);
    }
}
