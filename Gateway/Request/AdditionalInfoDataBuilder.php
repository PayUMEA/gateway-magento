<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class AdditionalInfoDataBuilder
 * @package PayU\Gateway\Gateway\Request
 */
class AdditionalInfoDataBuilder implements BuilderInterface
{
    public const ADDITIONAL_INFO = 'AdditionalInformation';
    public const SHOW_BUDGET = 'showBudget';
    public const SUPPORTED_METHODS = 'supportedPaymentMethods';
    public const SECURE_3D = 'secure3d';
    public const MERCHANT_REFERENCE = 'merchantReference';

    /**
     * Used in Discovery Miles payment method to determine
     * if staging or live environment is used.
     */
    public const DEMO_MODE = 'demoMode';

    /**
     * @param Config $config
     * @param SubjectReader $subjectReader
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(private readonly Config $config, private readonly SubjectReader $subjectReader)
    {
    }

    /**
     * @param array $buildSubject
     * @return array[]
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();
        $this->config->setMethodCode($payment->getMethod());

        return [
            self::ADDITIONAL_INFO => [
                self::SHOW_BUDGET => $this->config->isBudgetAllowed((int)$order->getStoreId()),
                self::SUPPORTED_METHODS => $this->config->getSupportedPaymentMethods((int)$order->getStoreId()),
                self::SECURE_3D => $this->config->isSecure3ds((int)$order->getStoreId()) ? 'true' : 'false',
                self::MERCHANT_REFERENCE => $order->getOrderIncrementId(),
                self::DEMO_MODE => $this->config->getEnvironment() === 'sandbox' ? 'true' : 'false'
            ]
        ];
    }
}
