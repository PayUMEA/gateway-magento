<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class ThreeDSecureDetailsHandler
 * @package PayU\Gateway\Gateway\Response
 */
class ThreeDSecureDetailsHandler implements HandlerInterface
{
    private const RESULT_CODE = 'resultCode';
    private const SECURE_3D_ID = 'secure3DId';

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(private readonly SubjectReader $subjectReader)
    {
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $transaction = $this->subjectReader->readTransaction($response);

        if ($payment->hasAdditionalInformation(self::SECURE_3D_ID)) {
            // remove 3d secure details for reorder
            $payment->unsAdditionalInformation(self::SECURE_3D_ID);
            $payment->unsAdditionalInformation(self::RESULT_CODE);
        }

        if (empty($transaction->getSecure3D())) {
            return;
        }

        $info = $transaction->getSecure3D();
        $payment->setAdditionalInformation(self::SECURE_3D_ID, $info->getSecure3DId());
        $payment->setAdditionalInformation(self::RESULT_CODE, $transaction->getResultCode());
    }
}
