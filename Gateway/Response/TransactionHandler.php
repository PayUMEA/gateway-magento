<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Response;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use PayU\Api\ResponseInterface;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class TransactionHandler
 * @package PayU\Gateway\Gateway\Response
 */
class TransactionHandler implements HandlerInterface
{
    /**
     * TransactionIdHandler constructor.
     * @param Config $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        protected readonly Config $config,
        protected readonly SubjectReader $subjectReader
    ) {
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        if (($orderPayment = $paymentDO->getPayment()) instanceof Payment) {
            $transactionInfo = $this->subjectReader->readResponse($response);

            $this->setTransactionId(
                $paymentDO->getOrder(),
                $orderPayment,
                $transactionInfo
            );

            $orderPayment->setIsTransactionClosed($this->shouldCloseTransaction($paymentDO->getOrder()));
            $closed = $this->shouldCloseParentTransaction($orderPayment);
            $orderPayment->setShouldCloseParentTransaction($closed);
            $txnAdditionalInfo = $orderPayment->getTransactionAdditionalInfo();
            $additionalInfo = $txnAdditionalInfo[Payment\Transaction::RAW_DETAILS] ?? [];

            $orderPayment->setTransactionAdditionalInfo(
                Payment\Transaction::RAW_DETAILS,
                $additionalInfo + $transactionInfo->getPaymentData()
            );
        }
    }

    /**
     * @param OrderAdapterInterface $order
     * @param InfoInterface $orderPayment
     * @param ResponseInterface $response
     * @return void
     */
    protected function setTransactionId(
        OrderAdapterInterface $order,
        InfoInterface $orderPayment,
        ResponseInterface $response
    ): void {
        $isCapture = $this->isCaptureTransaction((int)$order->getStoreId());

        if ($isCapture) {
            $orderPayment->setParentTransactionId($orderPayment->getTransactionId());
        }

        $payUReference = $response->getPayUReference();
        $orderPayment->setAdditionalInformation('payUReference', $payUReference);
        $orderPayment->setTransactionId($response->getPayUReference());
    }

    /**
     * Whether transaction should be closed
     *
     * @param OrderAdapterInterface $order
     * @return bool
     */
    protected function shouldCloseTransaction(OrderAdapterInterface $order): bool
    {
        $storeId = (int)$order->getStoreId();
        $isEnterprise = $this->config->isEnterprise($storeId);
        $isCapture = $this->isCaptureTransaction($storeId);

        return $isCapture && $isEnterprise;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param InfoInterface $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(InfoInterface $orderPayment): bool
    {
        return false;
    }

    /**
     * @param int $storeId
     * @return bool
     */
    protected function isCaptureTransaction(int $storeId): bool
    {
        $transactionType = $this->config->getTransactionType($storeId);

        return match ($transactionType) {
            'authorize', 'order',  => false,
            'authorize_capture' => true
        };
    }
}
