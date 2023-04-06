<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class CardDetailsHandler
 * @package PayU\Gateway\Gateway\Response
 */
class CardDetailsHandler implements HandlerInterface
{
    private const AMOUNT_IN_CENTS = 'amountInCents';
    private const CARD_EXPIRY = 'cardExpiry';
    private const CARD_NUMBER = 'cardNumber';
    private const GATEWAY_REFERENCE = 'gatewayReference';
    private const INFORMATION = 'information';
    private const NAME_ON_CARD = 'nameOnCard';

    /**
     * @var array
     */
    protected array $additionalInformationMapping = [
        self::AMOUNT_IN_CENTS,
        self::CARD_EXPIRY,
        self::CARD_NUMBER,
        self::GATEWAY_REFERENCE,
        self::INFORMATION,
        self::NAME_ON_CARD,
    ];

    /**
     * Constructor
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
        $transaction = $this->subjectReader->readTransaction($response);

        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        foreach ($this->additionalInformationMapping as $item) {
            $cardDetails = $transaction->getPaymentMethodsUsed();

            if (!$cardDetails || ($cardDetails && !isset($cardDetails->$item))) {
                continue;
            }

            $payment->setTransactionAdditionalInfo($item, $cardDetails->$item);
            $payment->setAdditionalInformation($item, $cardDetails->$item);
        }
    }
}
