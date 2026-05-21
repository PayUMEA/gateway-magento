<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Response;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use PayU\Gateway\Gateway\SubjectReader;
use PayU\Gateway\Model\Payment\TransferObjectFactory;
use PayU\Gateway\Model\Payment\Operations\TransactionUpdateOperation;

class CancelHandler implements HandlerInterface
{
    /**
     * @var DataObject
     */
    protected DataObject $transferObj;

    /**
     * @param SubjectReader $subjectReader
     * @param OrderFactory $orderFactory
     * @param TransferObjectFactory $transferFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param TransactionUpdateOperation $transactionUpdateOps
     */
    public function __construct(
        private readonly SubjectReader $subjectReader,
        private readonly OrderFactory $orderFactory,
        private readonly TransferObjectFactory $transferFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TransactionUpdateOperation $transactionUpdateOps
    ) {
        $this->transferObj = $this->transferFactory->create();
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        /** @var Payment $orderPayment */
        $orderPayment = $paymentDO->getPayment();

        $transactionInfo = $this->subjectReader->readResponse($response);
        $message = $transactionInfo->getResultMessage();
        $incrementId = $transactionInfo->getMerchantReference();

        if ($incrementId) {
            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
            $payment = $order->getPayment();

            if (!$payment || $payment->getMethod() != $orderPayment->getMethod()) {
                throw new LocalizedException(
                    __("This payment didn't work out because we can't find this order.")
                );
            }

            if ((int)$order->getId() > 0) {
                $message = __($message);
                $payment->setAmountCanceled($payment->getBaseAmountOrdered());
                $payment->setBaseAmountCanceled($payment->getBaseAmountOrdered());
                $this->transferObj->addData(['txn' => json_decode($transactionInfo->toJson())]);
                $this->transactionUpdateOps->update($order, $this->transferObj);

                $order->addCommentToStatusHistory($message);

                if ($orderPayment->getCheckTransactionStatus()) {
                    $this->transferObj->importTransactionInfo($orderPayment);
                } else {
                    $order->cancel();
                }

                $orderPayment->unsCheckTransactionStatus();
            }
        }
    }
}
