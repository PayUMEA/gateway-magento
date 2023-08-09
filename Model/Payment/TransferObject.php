<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment;

use Magento\Framework\Convert\ConvertArray;
use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;
use PayU\Gateway\Model\Constants\TransactionState;

/**
 * class ReturnHandler
 * @package PayU\Gateway\Model\Payment
 */
class TransferObject extends DataObject
{
    /**
     * @param array $data
     */
    public function __construct(
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * @return bool
     */
    public function isPaymentComplete(): bool
    {
        return $this->_getData('successful')
            && $this->getTransactionState() === TransactionState::SUCCESSFUL->value;
    }

    /**
     * @return bool
     */
    public function isAwaitingPayment(): bool
    {
        return $this->_getData('successful')
            && $this->getTransactionState() === TransactionState::AWAITING_PAYMENT->value;
    }

    /**
     * @return bool
     */
    public function isPaymentProcessing(): bool
    {
        return $this->_getData('successful')
            && $this->getTransactionState() === TransactionState::PROCESSING->value;
    }

    /**
     * @return bool
     */
    public function isPaymentNew(): bool
    {
        return $this->_getData('successful')
            && $this->getTransactionState() === TransactionState::NEW->value;
    }

    /**
     * @return string
     */
    public function getTranxId(): string
    {
        return $this->getData('payUReference');
    }

    /**
     * @return string
     */
    public function getPayUReference(): string
    {
        return $this->getTranxId();
    }

    /**
     * @return string
     */
    public function getResultCode(): string
    {
        return $this->getData('resultCode');
    }

    /**
     * @return string
     */
    public function getResultMessage(): string
    {
        return $this->getData('resultMessage');
    }

    /**
     * @return bool
     */
    public function hasCreditCard(): bool
    {
        return $this->getData('paymentMethodsUsed') !== null
            && isset($this->getData('paymentMethodsUsed')['cardNumber']);
    }

    /**
     * @return array
     */
    public function getCreditCardData(): array
    {
        return $this->hasCreditCard() ? $this->getData('paymentMethodsUsed') : [];
    }

    /**
     * @return string
     */
    public function getCreditCardNumber(): string
    {
        return $this->hasCreditCard() ? $this->getData('paymentMethodsUsed')['cardNumber'] : '';
    }

    /**
     * @return bool
     */
    public function hasEft(): bool
    {
        return $this->getData('paymentMethodsUsed') !== null
            && isset($this->getData('paymentMethodsUsed')['Eft']);
    }

    /**
     * @return array
     */
    public function getEftData(): array
    {
        return $this->hasEft() ? $this->getData('paymentMethodsUsed')['Eft'] : [];
    }

    /**
     * @return bool
     */
    public function hasGatewayReference(): bool
    {
        return $this->getData('paymentMethodsUsed') !== null
            && isset($this->getData('paymentMethodsUsed')['gatewayReference']);
    }

    /**
     * @return float
     */
    public function getTotalCaptured(): float
    {
        $paymentMethod = $this->getData('paymentMethodsUsed');

        if (!$paymentMethod) {
            $paymentMethod = $this->getData('basket');
        }

        return (float)($paymentMethod['amountInCents'] / 100);
    }

    /**
     * @return string
     */
    public function getDisplayMessage(): string
    {
        return $this->getData('displayMessage');
    }

    /**
     * @return bool
     */
    public function isFraudDetected(): bool
    {
        return $this->getData('fraud') !== null && $this->getData('fraud')['resultCode'];
    }

    /**
     * @return string
     */
    public function getMerchantReference(): string
    {
        return $this->getData('merchantReference');
    }

    /**
     * @return string
     */
    public function getTransactionState(): string
    {
        return $this->getData('transactionState');
    }

    /**
     * @return string
     */
    public function getTransactionType(): string
    {
        return $this->getData('transactionType');
    }

    /**
     * @return mixed|null
     */
    public function getPointOfFailure(): mixed
    {
        return $this->getData('pointOfFailure');
    }

    /**
     * @return mixed|null
     */
    public function getBasket(): mixed
    {
        return $this->getData('basket');
    }

    /**
     * Transfer transaction/payment information from API to order payment
     * @param InfoInterface $to
     * @return void
     */
    public function importTransactionInfo(InfoInterface $to): void
    {
        /**
         * Detect payment review and/or frauds
         */
        if ($this->isFraudDetected()) {
            $to->setIsTransactionPending(true);
            $to->setIsFraudDetected(true);
        }

        // give generic info about transaction state
        if ($this->isPaymentComplete()) {
            $to->setIsTransactionApproved(true);
        } elseif ($this->isAwaitingPayment()) {
            $to->setIsTransactionPending(true);
        } elseif ($this->isPaymentProcessing()) {
            $to->setIsTransactionProcessing(true);
        } elseif ($this->isPaymentNew()) {
            $to->setIsTransactionPending(true);
        } else {
            $to->setIsTransactionDenied(true);
        }

        $to->setTransactionAdditionalInfo('transactionInfo', $this);
    }

    /**
     * @return array
     */
    public function getPaymentData(): array
    {
        return ConvertArray::toFlatArray($this->toArray());
    }
}
