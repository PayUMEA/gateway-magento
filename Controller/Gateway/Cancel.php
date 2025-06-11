<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Controller\Gateway;

use Exception;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use PayU\Gateway\Controller\AbstractAction;

/**
 * class Cancel
 * @package PayU\Gateway\Controller\Gateway
 */
class Cancel extends AbstractAction implements HttpGetActionInterface
{
    /**
     * Cancel Express Checkout
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        try {
            $payUReference = $this->getPayUReference();
            $orderId = $this->getCheckoutSession()->getLastOrderId() ??
                $this->getCheckoutSession()->getData('last_order_id');
            $quoteId = $this->getCheckoutSession()->getLastSuccessQuoteId() ??
                $this->getCheckoutSession()->getData('last_success_quote_id');

            $order = $orderId ? $this->orderFactory->create()->load($orderId) : false;

            if ($payUReference &&
                $order &&
                $order->getQuoteId() == $quoteId
            ) {
                $this->responseProcessor->cancel($order, $payUReference);
            }

            $this->messageManager->addErrorMessage(
                __('Payment transaction unsuccessful. User canceled payment transaction.')
            );
        } catch (LocalizedException $ex) {
            $this->messageManager->addExceptionMessage($ex, $ex->getMessage());
            $this->logger->debug(['error' => "$orderId: " . $ex->getMessage()]);
        } catch (Exception $ex) {
            $this->messageManager->addExceptionMessage($ex, __('Unable to cancel Checkout'));
            $this->logger->debug(['error' => "$orderId: " . $ex->getMessage()]);
        }

        return $this->returnToCart();
    }
}
