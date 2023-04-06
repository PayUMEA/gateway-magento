<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment\Operations;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PayU\Gateway\Model\Constants\TransactionState;

/**
 * class HandleInstantPaymentNotification
 * @package PayU\Gateway\Model\Payment\Operations
 */
class HandleInstantPaymentNotification
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param AcceptPaymentOperation $acceptPaymentOperation
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AcceptPaymentOperation $acceptPaymentOperation
    ) {
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param DataObject $transactionInfo
     * @return void
     * @throws LocalizedException
     */
    public function notify(OrderInterface $order, OrderPaymentInterface $payment, DataObject $transactionInfo): void
    {
        if (
            in_array(
                $transactionInfo->getTransactionState(),
                array_column(TransactionState::cases(), 'value')
            )
        ) {
            $comment = "<strong>-----PAYU NOTIFICATION RECEIVED---</strong><br />";
            $amountBasket = $transactionInfo->getBasket()['amountInCents'] / 100;
            $amountPaid = ($transactionInfo->getCreditCardData()['amountInCents'] / 100) ?? 0;

            if (empty($amountPaid)) {
                $amountPaid = ($transactionInfo->getEftData()['amountInCents'] / 100) ?? 0;
            }

            $comment .= "Order Amount: " . $amountBasket . "<br />";
            $comment .= "Amount Paid: " . $amountPaid . "<br />";
            $comment .= "Merchant Reference : " . $transactionInfo->getMerchantReference() . "<br />";
            $comment .= "PayU Reference: " . $transactionInfo->getTranxId() . "<br />";
            $comment .= "PayU Payment Status: " . $transactionInfo->getTransactionState() . "<br /><br />";

            $paymentMethod = $transactionInfo->getCreditCardData() ?? [];

            if (empty($paymentMethod)) {
                $paymentMethod = $transactionInfo->getEftData() ?? [];
            }

            if (!empty($paymentMethod)) {
                if (is_array($paymentMethod)) {
                    $comment .= "<strong>Payment Method Details:</strong>";

                    foreach ($paymentMethod as $key => $value) {
                        $comment .= "<br />&nbsp;&nbsp;- " . $key . ":" . $value . " , ";
                    }
                }
            }

            // update order state
            switch ($transactionInfo->getTransactionState()) {
                // Payment completed
                case 'SUCCESSFUL':
                    $order->addCommentToStatusHistory($comment);
                    $this->acceptPaymentOperation->accept($payment);
                    break;
                case 'FAILED':
                case 'TIMEOUT':
                case 'EXPIRED':
                    $order->cancel();
                    break;
                default:
                    $order->addCommentToStatusHistory($comment, true);
                    break;
            }

            $this->orderRepository->save($order);
        }
    }
}
