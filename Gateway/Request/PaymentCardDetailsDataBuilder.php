<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Gateway\SubjectReader;
use PayU\Model\CreditCard;
use PayU\Model\FundingInstrument;

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
        $this->config->setMethodCode($payment->getMethod());

        $billingAddress = $order->getBillingAddress();

        $result[self::CARD] = null;
        $cardTypeMapper = $this->config->getCcTypesMapper();
        $cardData = $payment->getAdditionalInformation(PaymentInterface::KEY_ADDITIONAL_DATA);

        if ($cardData) {
            $card = new CreditCard();
            $card->setType(
                str_replace('-', '', strtoupper(array_flip($cardTypeMapper)[$cardData['cc_type']]))
            )
                ->setNumber($cardData['cc_number'])
                ->setExpiryMonth($this->addZeroPrefix($cardData['cc_exp_month']))
                ->setExpiryYear($cardData['cc_exp_year'])
                ->setCvv($cardData['cc_cid'])
                ->setNameOnCard(join(' ', [$billingAddress->getFirstname(), $billingAddress->getLastname()]))
                ->setBudget($this->config->isBudgetAllowed((int)$storeId))
                ->setSecure3D($this->config->isSecure3ds((int)$storeId));

            $funding = new FundingInstrument();
            $funding->setCreditCard($card)
                ->setSaveCard(true);

            $result[self::CARD] = $funding;
        }

        return $result;
    }

    /**
     * @param string $month
     * @return string
     */
    private function addZeroPrefix(string $month): string
    {
        if ($month === '1' || !str_starts_with($month, '1')) {
            $month = '0' . $month;
        }

        return $month;
    }
}
