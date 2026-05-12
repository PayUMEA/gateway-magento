<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\Gateway\Gateway\SubjectReader;

class SaleDataBuilder implements BuilderInterface
{
    public const PAYMENT = 'payment';

    /**
     * Description
     *
     * @var SubjectReader
     */
    private SubjectReader $subjectReader;

    /**
     * Description
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Description
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        return [
            self::PAYMENT => $payment
        ];
    }
}
