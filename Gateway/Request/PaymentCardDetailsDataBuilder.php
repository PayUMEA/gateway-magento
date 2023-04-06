<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use PayU\Api\FundingInstrument;
use PayU\Api\PaymentCard;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class PaymentCardDetailsDataBuilder
 * @package PayU\Gateway\Gateway\Request
 */
class PaymentCardDetailsDataBuilder implements BuilderInterface
{
    public const CARD = 'card';

    /**
     * @param Config $config
     * @param SubjectReader $subjectReader
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
        $storeId = $this->subjectReader->readStoreId($buildSubject);
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        $billingAddress = $order->getBillingAddress();

        $result[self::CARD] = null;
        $cardTypeMapper = $this->config->getCcTypesMapper();
        $cardData = $payment->getAdditionalInformation(PaymentInterface::KEY_ADDITIONAL_DATA);

        if ($cardData) {
            $card = new PaymentCard();
            $card->setType(
                str_replace('-', '', strtoupper(array_flip($cardTypeMapper)[$cardData['cc_type']]))
            )
                ->setNumber($cardData['cc_number'])
                ->setExpireMonth($cardData['cc_exp_month'])
                ->setExpireYear($cardData['cc_exp_year'])
                ->setCvv2($cardData['cc_cid'])
                ->setFirstName($billingAddress->getFirstname())
                ->setBillingCountry($billingAddress->getCountryId())
                ->setLastName($billingAddress->getLastname())
                ->setShowBudget($this->config->isBudgetAllowed((int)$storeId))
                ->setSecure3D($this->config->isSecure3ds((int)$storeId));

            $fi = new FundingInstrument();
            $fi->setPaymentCard($card)
                ->setStoreCard(true);

            $result[self::CARD] = $fi;
        }

        return $result;
    }
}
