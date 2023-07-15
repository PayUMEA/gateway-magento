<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class CancelHandler
 * @package PayU\Gateway\Gateway\Response
 */
class CancelHandler implements HandlerInterface
{
    /**
     * @param SubjectReader $subjectReader
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderPaymentRepositoryInterface $paymentRepository
     */
    public function __construct(
        private readonly SubjectReader $subjectReader,
        private readonly OrderFactory $orderFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderPaymentRepositoryInterface $paymentRepository
    ) {
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

        $message = 'Payment transaction amount of %1 was canceled by user on PayU.<br/>' . 'PayU reference "%2"<br/>';
        $responseObj = $this->subjectReader->readResponse($response);

        $payUReference = $responseObj->getPayUReference();
        $incrementId = $responseObj->getMerchantReference();

        if ($incrementId) {
            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
            $payment = $order->getPayment();

            if (!$payment || $payment->getMethod() != $orderPayment->getMethod()) {
                throw new LocalizedException(
                    __("This payment didn't work out because we can't find this order.")
                );
            }

            if ($order->getId()) {
                $message = __(
                    $message,
                    $order->getBaseCurrency()->formatTxt($order->getBaseTotalDue()),
                    $payUReference
                );

                $orderPayment->setIsTransactionClosed(true);
                $orderPayment->setShouldCloseParentTransaction(true);

                $order->addCommentToStatusHistory($message);
                $order->cancel();

                $this->paymentRepository->save($orderPayment);
                $this->orderRepository->save($order);
            }
        }
    }
}
