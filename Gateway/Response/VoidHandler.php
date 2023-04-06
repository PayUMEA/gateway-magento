<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Response;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Sales\Model\Order\Payment;
use PayU\Api\Response;

/**
 * class VoidHandler
 * @package PayU\Gateway\Gateway\Response
 */
class VoidHandler extends TransactionIdHandler
{
    /**
     * @param Payment $orderPayment
     * @param Response $transaction
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function setTransactionId(Payment $orderPayment, Response $transaction): void
    {
    }

    /**
     * @param OrderAdapterInterface $order
     * @return bool
     */
    protected function shouldCloseTransaction(OrderAdapterInterface $order): bool
    {
        return true;
    }

    /**
     * @param Payment $orderPayment
     * @return bool
     */
    protected function shouldCloseParentTransaction(Payment $orderPayment): bool
    {
        return true;
    }
}
