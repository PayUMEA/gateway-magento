<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment\Operations;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use PayU\Gateway\Model\Payment\AbstractOperation;
use PayU\Gateway\Model\Payment\TransferObject;

/**
 * class DenyPaymentOperation
 * @package PayU\Gateway\Model\Payment
 */
class DenyPaymentOperation extends AbstractOperation
{
    /**
     * @param InfoInterface $payment
     * @return void
     * @throws LocalizedException
     */
    public function deny(InfoInterface $payment): void
    {
        $order = $payment->getOrder();
        $transactionAdditionalInfo = $payment->getTransactionAdditionalInfo();

        /** @var TransferObject $transactionInfo */
        $transactionInfo = $transactionAdditionalInfo['transactionInfo'];

        $this->transactionOperation->update($order, $payment, $transactionInfo);
        $this->addStatusCommentOnUpdate($payment, $order, $transactionInfo);
        $order->cancel();
    }
}
