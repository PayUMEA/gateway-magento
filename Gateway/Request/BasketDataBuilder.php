<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\Gateway\Gateway\SubjectReader;
use PayUSdk\Model\Currency;
use PayUSdk\Model\Total;

/**
 * class BasketDataBuilder
 * @package PayU\Gateway\Gateway\Request
 */
class BasketDataBuilder implements BuilderInterface
{
    public const BASKET = 'basket';
    public const AMOUNT = 'amount';
    public const DESCRIPTION = 'description';
    public const MERCHANT_REFERENCE = 'merchantReference';

    /**
     * @param SubjectReader $subjectReader
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(private readonly SubjectReader $subjectReader)
    {
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        $currency = new Currency(['code' => $order->getCurrencyCode()]);
        $total = new Total();
        $total->setCurrency($currency)
            ->setAmount((float)$this->subjectReader->readAmount($buildSubject));

        return [
            self::BASKET => [
                self::AMOUNT => $total,
                self::DESCRIPTION => 'Order Reference#: ' . $order->getOrderIncrementId(),
                self::MERCHANT_REFERENCE => $order->getOrderIncrementId()
            ]
        ];
    }
}
