<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\Api\TransactionBase;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class TransactionTypeBuilder
 * @package PayU\Gateway\Gateway\Request
 */
class TransactionTypeBuilder implements BuilderInterface
{
    /**
     * The type of payment action such as RESERVE, PAYMENT, FINALIZE
     */
    public const TRANSACTION_TYPE = 'TransactionType';

    /**
     * Constructor
     *
     * @param Config $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        private readonly Config $config,
        private readonly SubjectReader $subjectReader
    ) {
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        $transactionType = $this->config->getTransactionType((int)$order->getStoreId());
        $type = match ($transactionType) {
            'authorize' => TransactionBase::TYPE_RESERVE,
            'authorize_capture', 'order' => TransactionBase::TYPE_PAYMENT
        };

        return [
            self::TRANSACTION_TYPE => $type
        ];
    }
}
