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

        $responseObj = $this->subjectReader->readResponse($response);

        if ($payment->hasAdditionalInformation(self::SECURE_3D_ID)) {
            // remove 3d secure details for reorder
            $payment->unsAdditionalInformation(self::SECURE_3D_ID);
            $payment->unsAdditionalInformation(self::RESULT_CODE);
        }

        $secure3d = $responseObj->getSecure3D();

        if ($secure3d->isEmpty()) {
            return;
        }

        $payment->setAdditionalInformation(self::SECURE_3D_ID, $secure3d->getSecure3DId());
        $payment->setAdditionalInformation(self::RESULT_CODE, $responseObj->getResultCode());
    }
}
