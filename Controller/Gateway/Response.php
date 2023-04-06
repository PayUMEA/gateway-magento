<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Controller\Gateway;

use Exception;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;
use PayU\Gateway\Controller\AbstractAction;

/**
 * class Response
 * @package PayU\Gateway\Controller\Gateway
 */
class Response extends AbstractAction implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * Retrieve transaction information and validates payment
     *
     * @return ResponseInterface|Redirect
     */
    public function execute()
    {
        $bypassPayURedirect = (bool)$this->getRedirectConfigData('bypass_payu_redirect');

        $processId = uniqid();
        $processString = self::class;

        $this->getSession()->setPayUProcessId($processId);
        $this->getSession()->setPayUProcessString($processString);

        $this->logger->info("($processId) START $processString");

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $message = __('');

        try {
            $payUReference = $this->initPayUReference();

            // if there is an order - load it
            $incrementId = $this->getCheckoutSession()->getLastRealOrderId()
                ?? $this->getCheckoutSession()->getData('last_real_order_id');

            /** @var Order $order */
            $order = $incrementId ? $this->orderFactory->create()->loadByIncrementId($incrementId) : false;

            if ($bypassPayURedirect) {
                $this->logger->info(
                    "($processId) ($incrementId) PayU Redirect Disabled, checking possible existing IPN status"
                );

                $orderState = $order->getState();

                // If the order is already a success
                if (in_array($orderState, [
                    Order::STATE_PROCESSING,
                    Order::STATE_COMPLETE
                ])) {
                    $this->logger->info(
                        "($processId) ($incrementId) PayU $processString ALREADY SUCCESS (from IPN) " .
                        "-> Redirect User"
                    );

                    return $this->sendSuccessPage($order);
                }

                // Or still pending
                if ($orderState == Order::STATE_PENDING_PAYMENT) {
                    $this->logger->info("($processId) ($incrementId) PayU $processString Order status pending");

                    return $this->sendPendingPage($order);
                }

                // Else there is a failure of some sort
                $this->messageManager->addExceptionMessage(
                    new LocalizedException(new Phrase('Unable to validate order')),
                    __('Unable to validate order')
                );
                $this->returnCustomerQuote(true, $message);

                return $resultRedirect->setPath('checkout/cart');
            } else {
                $this->logger->info(
                    "($processId) ($incrementId) PayU Redirect Enabled, processing redirect response."
                );
            }

            if ($order->getState() === Order::STATE_PROCESSING) {
                $this->logger->info(
                    "($processId) ($incrementId) PayU $processString ALREADY SUCCESS (from IPN) -> Redirect User"
                );

                return $this->sendSuccessPage($order);
            }

            if ($payUReference) {
                list($success, $message) = $this->responseProcessor->response($order, $payUReference);

                if ($success) {
                    return $this->sendSuccessPage($order);
                }

                $this->messageManager->addErrorMessage(__($message));
            }
        } catch (LocalizedException|Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Payment transaction validation failed.'));
        }

        $this->returnCustomerQuote(true, $message);

        return $resultRedirect->setPath('checkout/cart');
    }
}
