<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class CanCancelHandler
 * @package PayU\Gateway\Gateway\Config
 */
class CanCancelHandler implements ValueHandlerInterface
{
    /**
     * CanVoidHandler constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        private readonly SubjectReader $subjectReader
    ) {
    }

    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $subject, $storeId = null): bool
    {
        $paymentDO = $this->subjectReader->readPayment($subject);
        $payment = $paymentDO->getPayment();

        return $payment->getAmountOrdered() > 0 || $payment->getAmountAuthorized() > 0;
    }
}
