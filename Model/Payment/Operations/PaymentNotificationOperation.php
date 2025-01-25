<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment\Operations;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PayU\Gateway\Model\Constants\TransactionState;
use stdClass;

/**
 * class PaymentNotificationOperation
 * @package PayU\Gateway\Model\Payment\Operations
 */
class PaymentNotificationOperation
{
    /**
     * @param Logger $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param AcceptPaymentOperation $acceptPaymentOperation
     */
    public function __construct(
        private readonly Logger $logger,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AcceptPaymentOperation $acceptPaymentOperation
    ) {
    }

    /**
     * @param OrderInterface $order
     * @param OrderPaymentInterface $payment
     * @param stdClass $ipnData
     * @return void
     * @throws LocalizedException
     */
    public function notify(
        OrderInterface $order,
        OrderPaymentInterface $payment,
        stdClass $ipnData
    ): void {
        $transactionInfo = $payment->getTransactionAdditionalInfo()['transactionInfo'];

        if (
            in_array(
                $transactionInfo->getTransactionState(),
                array_column(TransactionState::cases(), 'value')
            )
        ) {
            $comment = "<strong>-----PAYU NOTIFICATION RECEIVED---</strong><br />";
            $totalDue = $transactionInfo->getTotalDue();
            $totalPaid = $transactionInfo->getTotalCaptured();

            $comment .= "Order Amount: " . $totalDue . "<br />";
            $comment .= "Amount Paid: " . $totalPaid . "<br />";
            $comment .= "Merchant Reference : " . $transactionInfo->getMerchantReference() . "<br />";
            $comment .= "PayU Reference: " . $transactionInfo->getTranxId() . "<br />";
            $comment .= "PayU Payment Status: " . $transactionInfo->getTransactionState() . "<br /><br />";

            if ($transactionInfo->hasPaymentMethod()) {
                $paymentMethods = $ipnData->PaymentMethodsUsed;

                if (!is_a($paymentMethods, \stdClass::class, true)) {
                    $paymentMethods = [$paymentMethods];
                }

                $comment .= "<strong>Payment Method Details:</strong>";

                foreach ($paymentMethods as $type => $paymentMethod) {
                    $comment .= "<br />===" . $type . "===";
                    foreach ($paymentMethod as $key => $value) {
                        $comment .= "<br />&nbsp;&nbsp;=> " . $key . ": " . $value;
                    }
                    $comment .= '<br />';
                }
            }

            // update order state
            switch ($transactionInfo->getTransactionState()) {
                // Payment completed
                case 'SUCCESSFUL':
                    $order->addCommentToStatusHistory($comment, 'processing');
                    $this->acceptPaymentOperation->accept($payment);
                    break;
                case 'PROCESSING':
                    $order->addCommentToStatusHistory($comment);
                    break;
                case 'FAILED':
                case 'TIMEOUT':
                case 'EXPIRED':
                    $order->addCommentToStatusHistory($comment);
                    $order->cancel();
                    break;
                default:
                    $order->addCommentToStatusHistory($comment, true);
                    break;
            }

            $this->orderRepository->save($order);
            $processId = $transactionInfo->getProcessId();
            $processClass = $transactionInfo->getProcessClass();
            $this->logger->debug(['info' => "IPN => ($processId) ($processClass): Processing complete."]);
        }
    }
}
