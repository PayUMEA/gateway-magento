<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment\Operations;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use PayU\Gateway\Model\Constants\TransactionType;
use PayU\Gateway\Model\Payment\AbstractOperation;
use PayU\Gateway\Model\Payment\TransferObject;

/**
 * class AcceptPaymentOperation
 * @package PayU\Gateway\Model\Payment\Operations
 */
class AcceptPaymentOperation extends AbstractOperation
{
    /**
     * @param InfoInterface $payment
     * @return void
     * @throws LocalizedException
     */
    public function accept(InfoInterface $payment): void
    {
        $isError = false;
        $transactionAdditionalInfo = $payment->getTransactionAdditionalInfo();

        /** @var TransferObject $transactionInfo */
        $transactionInfo = $transactionAdditionalInfo['transactionInfo'];
        $orderIncrementId = $transactionInfo->getMerchantReference();

        if ($orderIncrementId) {
            $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);

            $orderPayment = $order->getPayment();

            if (!$orderPayment || $orderPayment->getMethod() !== $payment->getMethodInstance()->getCode()) {
                throw new LocalizedException(
                    __("This payment didn't work out because we can't find this order.")
                );
            }

            if ($order->getId()) {
                try {
                    $this->validator->validate($order, $transactionInfo);
                    $this->fraudOperation->fraud($payment, $transactionInfo);

                    $this->addStatusCommentOnUpdate($payment, $order, $transactionInfo);
                    $this->updatePayment($payment, $transactionInfo);

                    $this->invoiceOperation->invoice($order);
                    $this->transactionOperation->update($order, $payment, $transactionInfo);
                } catch (LocalizedException|Exception $exception) {
                    $this->declineOrder($order, $transactionInfo, true, $exception->getMessage());

                    $isError = true;
                }
            } else {
                $isError = true;
            }
        } else {
            $isError = true;
        }

        if ($isError) {
            $responseText = $transactionInfo->getResultMessage();
            $responseText = $responseText && !$transactionInfo->isPaymentSuccessful()
                ? __($responseText)
                : __("This payment didn't work out because we can't find this order.");

            throw new LocalizedException($responseText);
        }
    }

    /**
     * Register order cancellation. Return money to customer if needed.
     *
     * @param Order $order
     * @param DataObject $transactionInfo
     * @param bool $voidPayment
     * @param ?string $message
     * @return void
     */
    private function declineOrder(
        Order $order,
        DataObject $transactionInfo,
        bool $voidPayment = false,
        ?string $message = ''
    ): void {
        $payment = $order->getPayment();

        try {
            if (
                $voidPayment &&
                $transactionInfo->getTranxId() &&
                strtoupper($transactionInfo->getTransactionType()) === TransactionType::PAYMENT->value
            ) {
                $this->addStatusCommentOnUpdate($payment, $order, $transactionInfo);
                $order->registerCancellation($message);
                $this->orderRepository->save($order);
            }
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
