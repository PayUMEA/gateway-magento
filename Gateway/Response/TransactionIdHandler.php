<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Response;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use PayU\Api\Response;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class TransactionIdHandler
 * @package PayU\Gateway\Gateway\Response
 */
class TransactionIdHandler implements HandlerInterface
{
    /**
     * TransactionIdHandler constructor.
     * @param Config $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        private readonly Config $config,
        private readonly SubjectReader $subjectReader
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
            $transaction = $this->subjectReader->readTransaction($response);

            $this->setTransactionId(
                $orderPayment,
                $transaction
            );

            $orderPayment->setIsTransactionClosed($this->shouldCloseTransaction($paymentDO->getOrder()));
            $closed = $this->shouldCloseParentTransaction($orderPayment);
            $orderPayment->setShouldCloseParentTransaction($closed);
        }
    }

    /**
     * @param Payment $orderPayment
     * @param Response $transaction
     * @return void
     */
    protected function setTransactionId(Payment $orderPayment, Response $transaction): void
    {
        $orderPayment->setTransactionId($transaction->getPayUReference());
    }

    /**
     * Whether transaction should be closed
     *
     * @param OrderAdapterInterface $order
     * @return bool
     */
    protected function shouldCloseTransaction(OrderAdapterInterface $order): bool
    {
        $transactionType = $this->config->getTransactionType($order->getStoreId());
        $isEnterprise = $this->config->isEnterprise($order->getStoreId());

        $shouldClose = match ($transactionType) {
            'authorize', 'order',  => false,
            'authorize_capture' => true
        };

        return $shouldClose && $isEnterprise;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $orderPayment): bool
    {
        return false;
    }
}
