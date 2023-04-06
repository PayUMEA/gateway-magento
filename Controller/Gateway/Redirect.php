<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Controller\Gateway;

use Exception;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\Controller\ResultFactory;
use PayU\Gateway\Controller\AbstractAction;

/**
 * class Redirect
 * @package PayU\Gateway\Controller\Gateway
 */
class Redirect extends AbstractAction implements HttpGetActionInterface
{
    /**
     * @return ResultRedirect
     */
    public function execute(): ResultRedirect
    {
        /** @var ResultRedirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $url = $this->getSession()->getCheckoutRedirectUrl();

            if ($url) {
                $this->getSession()->unsCheckoutRedirectUrl();

                return $resultRedirect->setPath($url);
            } else {
                $this->messageManager->addErrorMessage(
                    __('Unable to redirect to PayU: invalid redirect url.')
                );
            }
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Unable to redirect to PayU: server error encountered')
            );
        }

        $this->returnCustomerQuote(
            true,
            __('Unable to redirect to PayU: server error encountered')
        );

        return $resultRedirect->setPath('checkout/cart');
    }
}
