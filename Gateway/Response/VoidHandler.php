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
use PayUSdk\Api\ResponseInterface;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class VoidHandler
 * @package PayU\Gateway\Gateway\Response
 */
class VoidHandler implements HandlerInterface
{
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

        if (($orderPayment = $paymentDO->getPayment()) instanceof Payment) {
            $transaction = $this->subjectReader->readResponse($response);

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
     * @param OrderAdapterInterface $order
     * @param InfoInterface $orderPayment
     * @param ResponseInterface $response
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function setTransactionId(
        InfoInterface $orderPayment,
        ResponseInterface $response
    ): void {
        $voidTxnId = $response->getPayUReference();
        $parentTxnId = $orderPayment->getCcTransId();

        if ($parentTxnId !== $voidTxnId) {
            $orderPayment->setParentTransactionId($parentTxnId);
        }
    }

    /**
     * @param OrderAdapterInterface $order
     * @return bool
     */
    protected function shouldCloseTransaction(OrderAdapterInterface $order): bool
    {
        return true;
    }

    /**
     * @param InfoInterface $orderPayment
     * @return bool
     */
    protected function shouldCloseParentTransaction(InfoInterface $orderPayment): bool
    {
        return true;
    }
}
