<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper;
use PayU\Api\ResponseInterface;

/**
 * class SubjectReader
 * @package PayU\Gateway\Gateway
 */
class SubjectReader
{
    /**
     * Reads response object from subject
     *
     * @param array $subject
     * @return object
     */
    public function readResponseObject(array $subject): object
    {
        $response = Helper\SubjectReader::readResponse($subject);
        if (!isset($response['object']) || !is_object($response['object'])) {
            throw new InvalidArgumentException('Response object does not exist');
        }

        return $response['object'];
    }

    /**
     * Reads payment from subject
     *
     * @param array $subject
     * @return PaymentDataObjectInterface
     */
    public function readPayment(array $subject): PaymentDataObjectInterface
    {
        return Helper\SubjectReader::readPayment($subject);
    }

    /**
     * Reads transaction from the subject.
     *
     * @param array $subject
     * @return ResponseInterface
     * @throws InvalidArgumentException if the subject doesn't contain transaction details.
     */
    public function readResponse(array $subject): ResponseInterface
    {
        if (!isset($subject['object']) || !is_object($subject['object'])) {
            throw new InvalidArgumentException('Response object does not exist.');
        }

        return $subject['object'];
    }

    /**
     * Reads amount from subject
     *
     * @param array $subject
     * @return mixed
     */
    public function readAmount(array $subject): mixed
    {
        return Helper\SubjectReader::readAmount($subject);
    }

    /**
     * Reads customer id from subject
     *
     * @param array $subject
     * @return int
     */
    public function readCustomerId(array $subject): int
    {
        if (!isset($subject['customer_id'])) {
            throw new InvalidArgumentException('The "customerId" field does not exists');
        }

        return (int) $subject['customer_id'];
    }

    /**
     * Reads store's ID, otherwise returns null.
     *
     * @param array $subject
     * @return int|null
     */
    public function readStoreId(array $subject): ?int
    {
        return $subject['store_id'] ?? $subject['storeId'] ?? null;
    }

    /**
     * Reads customer id from subject
     *
     * @param array $subject
     * @return string
     */
    public function readTransactionId(array $subject): string
    {
        if (!isset($subject['transactionId'])) {
            throw new InvalidArgumentException('The "transactionId" field does not exists');
        }

        return $subject['transactionId'];
    }
}
