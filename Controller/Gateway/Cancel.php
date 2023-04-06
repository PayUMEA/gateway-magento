<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Controller\Gateway;

use Exception;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
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
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $payUReference = $this->initPayUReference();
            $orderId = $this->getCheckoutSession()->getLastOrderId();

            $order = $orderId ? $this->orderFactory->create()->load($orderId) : false;

            if ($payUReference &&
                $order &&
                $order->getQuoteId() == $this->getCheckoutSession()->getLastSuccessQuoteId()
            ) {
                $this->responseProcessor->cancel($order, $payUReference);

                $this->messageManager->addErrorMessage(
                    __('Payment transaction unsuccessful. User canceled payment transaction.')
                );
            } else {
                $this->messageManager->addErrorMessage(
                    __('Payment transaction unsuccessful.')
                );
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Unable to cancel Checkout'));
        }

        $this->returnCustomerQuote(true);

        return $resultRedirect->setPath('checkout/cart');
    }
}
