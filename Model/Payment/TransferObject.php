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
use Magento\Sales\Model\Order;
use PayU\Gateway\Model\Constants\TransactionState;
use PayU\Gateway\Model\Payment\Method\Masterpass;
use PayU\Gateway\Model\Payment\Method\Payflex;

class TransferObject extends DataObject
{
    /**
     * Check if payment is complete
     *
     * @return bool
     */
    public function isPaymentComplete(): bool
    {
        $state = TransactionState::tryFrom($this->getTransactionState());

        return $this->_getData('txn')->successful
            && $state === TransactionState::SUCCESSFUL;
    }

    /**
     * Check if awaiting payment
     *
     * @return bool
     */
    public function isAwaitingPayment(): bool
    {
        $state = TransactionState::tryFrom($this->getTransactionState());

        return $this->_getData('txn')->successful
            && $state === TransactionState::AWAITING_PAYMENT;
    }

    /**
     * Check if payment is processing
     *
     * @return bool
     */
    public function isPaymentProcessing(): bool
    {
        $state = TransactionState::tryFrom($this->getTransactionState());

        return $this->_getData('txn')->successful
            && $state === TransactionState::PROCESSING;
    }

    /**
     * Check if payment is new
     *
     * @return bool
     */
    public function isPaymentNew(): bool
    {
        $state = TransactionState::tryFrom($this->getTransactionState());

        return $this->_getData('txn')->successful
            && $state === TransactionState::NEW;
    }

    /**
     * Check if payment failed
     *
     * @return bool
     */
    public function isPaymentFailed(): bool
    {
        $state = TransactionState::tryFrom($this->getTransactionState());

        return ($this->_getData('txn')->successful === true || $this->_getData('txn')->successful === false)
            && in_array(
                $state,
                [TransactionState::FAILED, TransactionState::EXPIRED, TransactionState::TIMEOUT]
            );
    }

    /**
     * Get transaction id
     *
     * @return string
     */
    public function getTranxId(): string
    {
        return $this->_getData('txn')->payUReference;
    }

    /**
     * Get PayU reference
     *
     * @return string
     */
    public function getPayUReference(): string
    {
        return $this->getTranxId();
    }

    /**
     * Get result code
     *
     * @return string
     */
    public function getResultCode(): string
    {
        return $this->_getData('txn')->resultCode;
    }

    /**
     * @return string
     */
    public function getResultMessage(): string
    {
        return $this->_getData('txn')->resultMessage;
    }

    /**
     * @return bool
     */
    public function hasPaymentMethod(): bool
    {
        return isset($this->_getData('txn')->paymentMethodsUsed);
    }

    public function getPaymentMethods()
    {
        return $this->hasPaymentMethod() ? $this->_getData('txn')->paymentMethodsUsed : null;
    }

    /**
     * @return bool
     */
    public function hasCreditCard(): bool
    {
        return $this->isPaymentMethodCc();
    }

    /**
     * @return bool
     */
    public function isPaymentMethodCc(): bool
    {
        return $this->hasPaymentMethod() && $this->checkPaymentMethodCc();
    }

    public function checkPaymentMethodCc(): bool
    {
        $paymentMethods = $this->getPaymentMethods();

        if (is_array($paymentMethods)) {
            foreach ($paymentMethods as $method) {
                if ($this->getPropertyCaseInsensitive($method, 'gatewayReference')) {
                    return true;
                }
            }
        } else {
            if ($this->getPropertyCaseInsensitive($paymentMethods, 'gatewayReference')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getGatewayReference(): string
    {
        $gatewayReference = 'N/A';
        $paymentMethods = $this->getPaymentMethods();

        if (is_array($paymentMethods)) {
            foreach ($paymentMethods as $method) {
                if (property_exists($method, 'gatewayReference')) {
                    $gatewayReference = $method->gatewayReference;
                }
            }
        } else {
            if (property_exists($paymentMethods, 'gatewayReference')) {
                $gatewayReference = $paymentMethods->gatewayReference;
            }
        }

        return $gatewayReference;
    }

    /**
     * @return array
     */
    public function getCardData(): array
    {
        $cardData = [];
        $hasCc = $this->isPaymentMethodCc();

        if ($hasCc) {
            $paymentMethods = $this->getPaymentMethods();

            if (is_array($paymentMethods)) {
                foreach ($paymentMethods as $method) {
                    if ($this->getPropertyCaseInsensitive($method, 'cardNumber')) {
                        $cardData['cardNumber'] = $this->getPropertyCaseInsensitive($method, 'cardNumber');
                        $cardData['owner'] = $this->getPropertyCaseInsensitive($method, 'nameOnCard');
                        $cardData['txnId'] = $this->getPropertyCaseInsensitive($method, 'gatewayReference');
                        $cardData['expiryYear'] = substr($this->getPropertyCaseInsensitive($method, 'cardExpiry'), -4);
                        $cardData['type'] = $this->getPropertyCaseInsensitive($method, 'information');
                    }
                }
            } else {
                if ($this->getPropertyCaseInsensitive($paymentMethods, 'cardNumber')) {
                    $cardData['cardNumber'] = $this->getPropertyCaseInsensitive($paymentMethods, 'cardNumber');
                    $cardData['owner'] = $this->getPropertyCaseInsensitive($paymentMethods, 'nameOnCard');
                    $cardData['txnId'] = $this->getPropertyCaseInsensitive($paymentMethods, 'gatewayReference');
                    $cardData['expiryYear'] = substr($this->getPropertyCaseInsensitive($paymentMethods, 'cardExpiry'), -4);
                    $cardData['type'] = $this->getPropertyCaseInsensitive($paymentMethods, 'information');
                }
            }
        }

        return $cardData;
    }

    /**
     * @return float
     */
    public function getTotalDue(): float
    {
        $basket = $this->getBasket();
        $totalDue = $this->getPropertyCaseInsensitive($basket, 'amountInCents');

        if (is_null($totalDue)) {
            $totalDue = 0.0;

            return $totalDue;
        }

        return $totalDue / 100;
    }

    /**
     * @return float
     */
    public function getTotalCaptured(): float
    {
        $total = 0.0;

        if ($this->isPaymentNew() || $this->isPaymentFailed()) {
            return $total;
        }

        $paymentMethods = $this->getPaymentMethods();

        if (!$paymentMethods) {
            return $total;
        }

        if (!is_array($paymentMethods)) {
            $paymentMethods = [$paymentMethods];
        }

        foreach ($paymentMethods as $paymentMethod) {
            $total += $this->getPropertyCaseInsensitive($paymentMethod, 'amountInCents');
        }

        return $total / 100;
    }

    /**
     * @return string
     */
    public function getDisplayMessage(): string
    {
        return $this->getData('txn')->displayMessage;
    }

    /**
     * @return bool
     */
    public function isFraudDetected(): bool
    {
        return isset($this->getData('txn')->fraud) && isset($this->getData('txn')->fraud->resultCode);
    }

    /**
     * @return string
     */
    public function getMerchantReference(): string
    {
        return $this->getData('txn')->merchantReference;
    }

    /**
     * @return string
     */
    public function getTransactionState(): string
    {
        return $this->getData('txn')->transactionState;
    }

    /**
     * @return string
     */
    public function getTransactionType(): string
    {
        return $this->getData('txn')->transactionType;
    }

    /**
     * @return mixed|null
     */
    public function getPointOfFailure(): mixed
    {
        return $this->getData('txn')->pointOfFailure;
    }

    /**
     * @return mixed|null
     */
    public function getBasket(): mixed
    {
        return $this->getData('txn')->basket;
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

        if ($to->getCaptureOperationCalled() || $to->getCheckTransactionStatus()) {
            $to->setTransactionAdditionalInfo(
                Order\Payment\Transaction::RAW_DETAILS,
                $this->getPaymentData()
            );
            $to->setTransactionAdditionalInfo('transactionInfo', []);
        }
    }

    /**
     * @return array
     */
    public function getPaymentData(): array
    {
        return $this->toFlatArray(
            json_decode(
                json_encode(
                    $this->toArray()['txn']
                ),
                true
            )
        );
    }

    private function toFlatArray(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $flatKey = $prefix !== '' ? $prefix . '_' . $key : $key;

            if (is_array($value)) {
                $flatArray = self::toFlatArray($value, $flatKey);
                foreach ($flatArray as $flatKeySub => $flatValueSub) {
                    $result[$flatKeySub] = $flatValueSub;
                }
            } else {
                $result[$flatKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Is Canceled Payflex transaction
     *
     * @param Order $order
     * @return bool
     */
    public function isCancelPayflex(Order $order)
    {
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();

        return $method->getCode() === Payflex::CODE && ($this->isPaymentProcessing() || $this->isPaymentFailed());
    }

    /**
     * Is Canceled Payflex transaction
     *
     * @param Order $order
     * @return bool
     */
    public function isMasterpassTimeout(Order $order)
    {
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();

        return $method->getCode() === Masterpass::CODE && ($this->isPaymentProcessing() || $this->isPaymentFailed());
    }

    /**
     * Retrieves a property from a stdClass object in a case-insensitive manner.
     * Normalizes all property keys to lowercase for comparison.
     *
     * @param \stdClass $object    The object to search.
     * @param string   $property  The property name to find (any case).
     * @return mixed              The property value, or null if not found.
     */
    function getPropertyCaseInsensitive(\stdClass $object, string $property): mixed
    {
        $needle = strtolower(str_replace('_', '', $property));

        foreach (get_object_vars($object) as $key => $value) {
            $normalizedKey = strtolower(str_replace('_', '', $key));

            if ($normalizedKey === $needle) {
                return $value;
            }
        }

        return null;
    }
}
