<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment\Operations;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PayU\Gateway\Model\Payment\AbstractOperation;
use PayU\Gateway\Model\Payment\TransferObject;

class DenyPaymentOperation extends AbstractOperation
{
    /**
     * Description
     *
     * @param OrderPaymentInterface $payment
     * @param ?string $comment
     * @return void
     * @throws LocalizedException
     */
    public function deny(OrderPaymentInterface $payment, ?string $comment = null): void
    {
        $order = $payment->getOrder();
        $transactionAdditionalInfo = $payment->getTransactionAdditionalInfo();
        /** @var TransferObject $transactionInfo */
        $transactionInfo = $transactionAdditionalInfo['transactionInfo'];

        $processId = $transactionInfo->getProcessId();
        $processClass = $transactionInfo->getProcessClass();

        if (!$order->canCancel()) {
            $this->logger->debug(
                "IPN => ($processId) ($processClass) : order already canceled.",
                ['info' => "", 'response' => $transactionInfo]
            );

            return;
        }

        $order->cancel();
        $this->addStatusCommentOnUpdate($order, $payment, $transactionInfo);

        if ($comment) {
            $order->addCommentToStatusHistory($comment, true);
        }

        $this->transactionOperation->update($order, $transactionInfo);
        $this->orderRepository->save($order);
    }
}
