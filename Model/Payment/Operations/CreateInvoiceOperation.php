<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment\Operations;

use Exception;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\Generic;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Service\InvoiceService;

/**
 * class CreateInvoiceOperation
 * @package PayU\Gateway\Model\Payment\Operations
 */
class CreateInvoiceOperation
{
    /**
     * @param Generic $session
     * @param Config $orderConfig
     * @param InvoiceSender $invoiceSender
     * @param InvoiceService $invoiceService
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param Transaction $transaction
     * @param Logger $logger
     */
    public function __construct(
        private readonly Generic $session,
        private readonly Config $orderConfig,
        private readonly InvoiceSender $invoiceSender,
        private readonly InvoiceService $invoiceService,
        private readonly OrderFactory $orderFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderSender $orderSender,
        private readonly Transaction $transaction,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param OrderInterface $order
     * @return void
     * @throws LocalizedException
     */
    public function invoice(OrderInterface $order, string $processId, string $processClass): void
    {
        $id = $order->getIncrementId();

        try {
            $order->setCanSendNewEmailFlag(true);
            $this->orderSender->send($order);

            $this->logger->debug(
                ['info' => "($id) ($processId) ($processClass) : can_invoice (initial check): " . $order->canInvoice()]
            );

            if ($order->canInvoice()) {
                /**
                 * 2020/10/23 Double Invoice Correction
                 * Force reload order state to check status just before update,
                 * discard invoice if status changed since start of process
                 */
                $dupOrder = $this->orderFactory->create()->loadByIncrementId($order->getIncrementId());
                $this->logger->debug(
                    ['info' => "($id) ($processId) ($processClass) : can_invoice (double check): " . $order->canInvoice()]
                );

                if (!$dupOrder->canInvoice()) {
                    // Just skip to else clause
                    goto cannot_invoice_marker;
                }

                $status = $this->orderConfig->getStateDefaultStatus('processing');
                $order->setState('processing')->setStatus($status);

                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
                $invoice->register();

                $transactionService = $this->transaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionService->save();

                $this->logger->debug(['info' => "INVOICED => ($id) ($processId) ($processClass)"]);

                $this->invoiceSender->send($invoice);

                $order->addCommentToStatusHistory(
                    __('Notified customer about invoice #%1.', $invoice->getId())
                )->setIsCustomerNotified(true);

                $this->orderRepository->save($order);
            } else {
                /**
                 * Double Invoice Correction
                 * 2020/10/23
                 */
                cannot_invoice_marker:
                $this->logger->debug(['info' => "($id) ($processId) ($processClass) : already invoiced, skipped."]);
            }
        } catch (Exception $e) {
            throw new LocalizedException(__("Error encountered while capturing your order"));
        }
    }
}
