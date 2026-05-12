<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * This builder is used for resolving correct store and used only to retrieve correct store config.
 * The data from this builder won't be sent to PayU Gateway.
 *
 * class StoreConfigBuilder
 */
class StoreConfigBuilder implements BuilderInterface
{
    public const STORE_ID = 'storeId';
    public const METHOD_CODE = 'methodCode';

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
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        return [
            self::STORE_ID => $order->getStoreId(),
            self::METHOD_CODE => $payment->getMethod()
        ];
    }
}
