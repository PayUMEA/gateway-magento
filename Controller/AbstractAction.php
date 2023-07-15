<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Controller;

use Exception;
use Magento\Checkout\Controller\Express\RedirectLoginInterface;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Session\Generic;
use Magento\Framework\Url\Helper\Data;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Model\Payment\Processor;
use Psr\Log\LoggerInterface;

/**
 * class Response
 * @package PayU\Gateway\Controller\Gateway
 */
abstract class AbstractAction implements ActionInterface, RedirectLoginInterface
{
    /**
     * @var RedirectInterface
     */
    protected RedirectInterface $redirect;

    /**
     * @var ActionFlag
     */
    protected ActionFlag $actionFlag;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * @var ResultFactory
     */
    protected ResultFactory $resultFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected ObjectManagerInterface $objectManager;

    /**
     * @var MessageManagerInterface
     */
    protected MessageManagerInterface $messageManager;

    /**
     * AbstractAction constructor.
     * @param Context $context
     * @param Data $urlHelper
     * @param Url $customerUrl
     * @param Config $config
     * @param Generic $payuSession
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param OrderFactory $orderFactory
     * @param Processor $responseProcessor
     * @param CustomerSession $customerSession
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected Context $context,
        protected Data $urlHelper,
        protected Url $customerUrl,
        protected Config $config,
        protected Generic $payuSession,
        protected LoggerInterface $logger,
        protected Session $checkoutSession,
        protected OrderFactory $orderFactory,
        protected Processor $responseProcessor,
        protected CustomerSession $customerSession,
        protected ScopeConfigInterface $scopeConfig
    ) {
        $this->redirect = $this->context->getRedirect();
        $this->actionFlag = $this->context->getActionFlag();
        $this->request = $this->context->getRequest();
        $this->response = $this->context->getResponse();
        $this->resultFactory = $this->context->getResultFactory();
        $this->objectManager = $this->context->getObjectManager();
        $this->messageManager = $this->context->getMessageManager();
    }

    /**
     * @return Generic
     */
    protected function getSession(): Generic
    {
        return $this->payuSession;
    }

    /**
     * @return Session
     */
    protected function getCheckoutSession(): Session
    {
        return $this->checkoutSession;
    }

    /**
     * Returns before_auth_url redirect parameter for customer session
     * @return null
     */
    public function getCustomerBeforeAuthUrl()
    {
        return;
    }

    /**
     * Returns a list of action flags [flag_key] => boolean
     * @return array
     */
    public function getActionFlagList(): array
    {
        return [];
    }

    /**
     * Returns login url parameter for redirect
     * @return string
     */
    public function getLoginUrl(): string
    {
        return $this->customerUrl->getLoginUrl();
    }

    /**
     * Returns action name which requires redirect
     * @return string
     */
    public function getRedirectActionName(): string
    {
        return 'redirect';
    }

    /**
     * Redirect to login page
     *
     * @return void
     */
    public function redirectLogin(): void
    {
        $this->actionFlag->set('', 'no-dispatch', true);
        $this->customerSession->setBeforeAuthUrl($this->redirect->getRefererUrl());
        $this->getResponse()->setRedirect(
            $this->urlHelper->addRequestParam($this->customerUrl->getLoginUrl(), ['context' => 'checkout'])
        );
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Search for proper checkout reference in request or session or (un)set specified one
     * Combined getter/setter
     *
     * @param ?string $payUReference
     * @return ?string
     * @throws LocalizedException
     */
    protected function initPayUReference(?string $payUReference = null): ?string
    {
        if (null !== $payUReference) {
            if (!$payUReference) {
                // security measure for avoid unsetting reference twice
                if (!$this->getSession()->getCheckoutReference()) {
                    throw new LocalizedException(
                        __('PayU reference does not exist.')
                    );
                }
                $this->getSession()->unsCheckoutReference();
            } else {
                $this->getSession()->setCheckoutReference($payUReference);
            }

            return $payUReference;
        }

        $reference = $this->getRequest()->getParam('PayUReference') ?:
            $this->getRequest()->getParam('payUReference');

        if ($reference) {
            if ($reference !== $this->getSession()->getCheckoutReference()) {
                throw new LocalizedException(
                    __('Invalid PayU transaction id.')
                );
            }
        } else {
            $reference = $this->getSession()->getCheckoutReference();
        }

        return $reference;
    }

    /**
     * @return void
     */
    protected function clearSessionData(): void
    {
        $this->getSession()->unsCheckoutReference();
        $this->getSession()->unsCheckoutRedirectUrl();
        $this->getSession()->unsCheckoutOrderIncrementId();
    }

    /**
     * Set redirect into response
     *
     * @param string $path
     * @param array $arguments
     * @return ResponseInterface
     */
    protected function redirect(string $path, array $arguments = []): ResponseInterface
    {
        $this->redirect->redirect($this->getResponse(), $path, $arguments);

        return $this->getResponse();
    }

    /**
     * @param Order $order
     * @return ResponseInterface
     */
    protected function sendPendingPage(Order $order): ResponseInterface
    {
        $this->getCheckoutSession()
            ->setLastQuoteId($order->getQuoteId())
            ->setLastSuccessQuoteId($order->getQuoteId());

        $this->getCheckoutSession()
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());

        $this->messageManager->addSuccessMessage(
            __('Your order was placed and will be processed once payment is confirmed.')
        );

        $this->clearSessionData();

        return $this->redirect('checkout/onepage/success');
    }

    /**
     * @param Order $order
     * @return ResponseInterface
     */
    protected function sendSuccessPage(Order $order): ResponseInterface
    {
        $this->getCheckoutSession()
            ->setLastQuoteId($order->getQuoteId())
            ->setLastSuccessQuoteId($order->getQuoteId());

        $this->getCheckoutSession()
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());

        $this->messageManager->addSuccessMessage(
            __('Payment was successful and we received your order with much fanfare!')
        );

        $this->clearSessionData();

        return $this->redirect('checkout/onepage/success');
    }

    /**
     * @param string $field
     * @param null $storeId
     * @return mixed
     */
    public function getRedirectConfigData(string $field, $storeId = null): mixed
    {
        $path = 'payment/payu_gateway/' . $field;

        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Return customer quote
     *
     * @param bool $cancelOrder
     * @param ?Phrase $errorMsg
     * @return void
     */
    protected function returnCustomerQuote(bool $cancelOrder = false, ?Phrase $errorMsg = null): void
    {
        $incrementId = $this->getCheckoutSession()->getLastRealOrderId()
            ?? $this->getCheckoutSession()->getData('last_real_order_id');

        if ($incrementId) {
            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);

            if ($order->getId()) {
                try {
                    /** @var CartRepositoryInterface $quoteRepository */
                    $quoteRepository = $this->objectManager->create(CartRepositoryInterface::class);
                    /** @var Quote $quote */
                    $quote = $quoteRepository->get($order->getQuoteId());

                    $quote->setIsActive(true)->setReservedOrderId(null);
                    $quoteRepository->save($quote);
                    $this->getCheckoutSession()->replaceQuote($quote);

                    $this->getSession()->unsCheckoutOrderIncrementId($incrementId);
                    $this->getSession()->unsetData('quote_id');

                    $this->clearSessionData();

                    if ($cancelOrder) {
                        $order->registerCancellation($errorMsg)->save();
                    }
                } catch (NoSuchEntityException|LocalizedException|Exception $e) {
                }
            }
        }
    }

    /**
     * @param string $httpCode
     * @param $text
     * @return void
     */
    protected function respond(string $httpCode = '200', $text = null): void
    {
        if ($httpCode === '200') {
            if (is_callable('fastcgi_finish_request')) {
                if ($text !== null) {
                    echo $text;
                }

                session_write_close();
                fastcgi_finish_request();

                return;
            }
        }

        ignore_user_abort(true);
        ob_start();

        if ($text !== null) {
            echo $text;
        }

        $serverProtocol = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
        header($serverProtocol . " {$httpCode} OK");
        header('Content-Encoding: none');
        header('Content-Length: ' . ob_get_length());
        header('Connection: close');

        ob_end_flush();
        ob_flush();
        flush();
    }
}
