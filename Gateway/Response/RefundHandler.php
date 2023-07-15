<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Response;

use Magento\Payment\Model\InfoInterface;

/**
 * class RefundHandler
 * @package PayU\Gateway\Gateway\Response
 */
class RefundHandler extends VoidHandler
{
    /**
     * Whether parent transaction should be closed
     *
     * @param InfoInterface $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(InfoInterface $orderPayment): bool
    {
        return !$orderPayment->getCreditmemo()->getInvoice()->canRefund();
    }
}
