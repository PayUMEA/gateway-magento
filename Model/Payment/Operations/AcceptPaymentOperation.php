<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment\Operations;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PayU\Gateway\Model\Payment\AbstractOperation;

/**
 * class AcceptPaymentOperation
 * @package PayU\Gateway\Model\Payment\Operations
 */
class AcceptPaymentOperation extends AbstractOperation
{
    /**
     * @param InfoInterface $payment
     * @param string $processId
     * @param string $processClass
     * @return void
     * @throws LocalizedException
     */
    public function accept(OrderPaymentInterface $payment): void
    {
        $isError = false;
        $transactionInfo = $payment->getTransactionAdditionalInfo()['transactionInfo'];

        $processId = $transactionInfo->getProcessId();
        $processClass = $transactionInfo->getProcessClass();
        $orderIncrementId = $transactionInfo->getMerchantReference();

        if ($orderIncrementId) {
            $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);

            $orderPayment = $order->getPayment();

            if (!$orderPayment || $orderPayment->getMethod() !== $payment->getMethodInstance()->getCode()) {
                throw new LocalizedException(
                    __("The payment transaction didn't work out because we can't find order.")
                );
            }

            if ($order->getId()) {
                try {
                    $this->validator->validate($order, $transactionInfo);
                    $this->fraudOperation->fraud($payment, $transactionInfo);

                    $this->addStatusCommentOnUpdate($payment, $order, $transactionInfo);
                    $this->updatePayment($payment, $transactionInfo);

                    $this->invoiceOperation->invoice($order, $processId, $processClass);
                    $this->transactionOperation->update($order, $payment, $transactionInfo);
                } catch (LocalizedException|Exception $exception) {
                    $order->addCommentToStatusHistory($exception->getMessage());

                    $isError = true;
                }
            } else {
                $isError = true;
            }
        } else {
            $isError = true;
        }

        $this->orderRepository->save($order);

        if ($isError) {
            $responseText = "The payment transaction didn't work out.";
            $responseText = !$transactionInfo->isPaymentComplete()
                ? __($transactionInfo->getResultMessage())
                : __($responseText);

            throw new LocalizedException($responseText);
        }
    }
}
