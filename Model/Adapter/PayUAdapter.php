<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Adapter;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use PayU\Api\LookupTransaction;
use PayU\Api\Payment;
use PayU\Api\Redirect;
use PayU\Api\Refund;
use PayU\Api\Reserve;
use PayU\Api\Response;
use PayU\Api\Transaction;
use PayU\Api\TransactionBase;
use PayU\Api\VoidTransaction;
use PayU\Auth\BasicAuth;
use PayU\Gateway\Gateway\Request\AdditionalInfoDataBuilder;
use PayU\Gateway\Gateway\Request\AddressDataBuilder;
use PayU\Gateway\Gateway\Request\BasketDataBuilder;
use PayU\Gateway\Gateway\Request\CaptureDataBuilder;
use PayU\Gateway\Gateway\Request\CustomerDataBuilder;
use PayU\Gateway\Gateway\Request\FraudDataBuilder;
use PayU\Gateway\Gateway\Request\PaymentCardDetailsDataBuilder;
use PayU\Gateway\Gateway\Request\PaymentUrlDataBuilder;
use PayU\Gateway\Gateway\Request\RefundDataBuilder;
use PayU\Gateway\Gateway\Request\TransactionInfoDataBuilder;
use PayU\Gateway\Gateway\Request\TransactionTypeBuilder;
use PayU\Resource;
use PayU\Soap\ApiContext;

/**
 * class PayUAdapter
 * @package PayU\Gateway\Model\Adapter
 */
class PayUAdapter
{
    /**
     * @var ?ApiContext
     */
    protected ?ApiContext $apiContext = null;

    /**
     * @param string $safeKey
     * @param string $username
     * @param string $password
     * @param string $environment
     * @param bool $enterprise
     * @param string $paymentMethods
     * @param DirectoryList $directoryList
     */
    public function __construct(
        private readonly string $safeKey,
        private readonly string $username,
        private readonly string $password,
        private readonly string $environment,
        private readonly bool   $enterprise,
        private readonly string $paymentMethods,
        private readonly DirectoryList $directoryList
    ) {
        $this->initApi();
    }

    /**
     * @return void
     */
    private function initApi(): void
    {
        if (!$this->apiContext) {
            $this->apiContext = new ApiContext(
                new BasicAuth(
                    $this->username,
                    $this->password,
                    $this->safeKey
                )
            );

            try {
                $logFile = $this->directoryList->getPath('log') . DIRECTORY_SEPARATOR . 'payu_gateway.log';
            } catch (FileSystemException $exception) {
                $logFile = 'payu_gateway.log';
            }

            $this->apiContext->setConfig(
                [
                    'mode' => $this->environment,
                    'log.log_enabled' => $this->environment === 'sandbox',
                    'log.file_name' => $logFile,
                    'log.log_level' => 'DEBUG',
                    'cache.enabled' => true,
                    'default_account.payment_methods' => $this->paymentMethods
                ]
            );
        }

        $this->apiContext->setAccountId('default_account')
            ->setIntegration(
                $this->enterprise ?
                    ApiContext::ENTERPRISE :
                    ApiContext::REDIRECT
            );
    }

    /**
     * @param array $attributes
     * @return Resource
     */
    public function sale(array $attributes): Resource
    {
        return match ($this->enterprise) {
            true => $this->doEnterprise($attributes),
            false => $this->doRedirect($attributes)
        };
    }

    /**
     * @param array $attributes
     * @return Resource
     */
    public function order(array $attributes): Resource
    {
        return $this->doRedirect($attributes);
    }

    /**
     * @param $reference
     * @return Response
     * @throws LocalizedException
     */
    public function search($reference): Response
    {
        $response = LookupTransaction::get($reference, $this->apiContext);

        if (!$response->getReturn()) {
            throw new LocalizedException(__('PayU Gateway error encountered.'));
        }

        return $response->getReturn();
    }

    /**
     * @param array $attributes
     * @return Response
     * @throws LocalizedException
     */
    public function transactionInfo(array $attributes): Response
    {
        $payUReference = $attributes[TransactionInfoDataBuilder::PAYU_REFERENCE]
            ?? $attributes['payment']->getTransactionId()
            ?? $attributes['payment']->getLastTransId();

        if (!$payUReference) {
            throw new LocalizedException(__('Invalid payU Reference'));
        }

        return $this->search($payUReference);
    }

    /**
     * @param array $attributes
     * @return Resource
     */
    public function capture(array $attributes): Resource
    {
        $reserve = new Reserve();
        $reserve->setIntent(TransactionBase::TYPE_FINALIZE)
            ->setCustomer($attributes[CaptureDataBuilder::CUSTOMER])
            ->setTransaction($attributes[CaptureDataBuilder::TRANSACTION])
            ->setPayUReference($attributes[CaptureDataBuilder::PAYU_REFERENCE])
            ->setMerchantReference($attributes[CaptureDataBuilder::MERCHANT_REFERENCE]);

        return $reserve->capture($this->apiContext);
    }

    /**
     * @param array $attributes
     * @return Resource
     */
    public function refund(array $attributes): Resource
    {
        $refund = new Refund();
        $refund->setIntent(TransactionBase::TYPE_CREDIT)
            ->setTransaction($attributes[RefundDataBuilder::TRANSACTION])
            ->setPayUReference($attributes[RefundDataBuilder::PAYU_REFERENCE])
            ->setMerchantReference($attributes[RefundDataBuilder::MERCHANT_REFERENCE]);

        return $refund->refund($this->apiContext);
    }

    /**
     * @param array $attributes
     * @return Resource
     */
    public function void(array $attributes): Resource
    {
        $void = new VoidTransaction();
        $void->setIntent(TransactionBase::TYPE_RESERVE_CANCEL)
            ->setTransaction($attributes[RefundDataBuilder::TRANSACTION])
            ->setPayUReference($attributes[RefundDataBuilder::PAYU_REFERENCE])
            ->setMerchantReference($attributes[RefundDataBuilder::MERCHANT_REFERENCE]);

        return $void->void($this->apiContext);
    }

    /**
     * @param array $attributes
     * @return Resource
     */
    private function doEnterprise(array $attributes): Resource
    {
        $payment = new Payment();
        $transaction = new Transaction();

        $basket = $attributes[BasketDataBuilder::BASKET];
        $itemList = $attributes[FraudDataBuilder::ITEM_LIST];
        $customer = $attributes[CustomerDataBuilder::CUSTOMER];
        $fraudManagement = $attributes[FraudDataBuilder::FRAUD];
        $shippingInfo = $attributes[AddressDataBuilder::SHIPPING_INFO];
        $fundingInstrument = $attributes[PaymentCardDetailsDataBuilder::CARD];

        if ($fundingInstrument) {
            $customer->setPaymentMethod(
                $attributes[AdditionalInfoDataBuilder::ADDITIONAL_INFO][AdditionalInfoDataBuilder::SUPPORTED_METHODS]
            );
            $customer->setFundingInstrument($fundingInstrument);
        }

        if ($fraudManagement && $itemList) {
            $transaction->setItemList($itemList);
            $transaction->setFraudManagement($fraudManagement);
        }

        $transaction->setAmount($basket[BasketDataBuilder::AMOUNT])
            ->setDescription($basket[BasketDataBuilder::DESCRIPTION])
            ->setReferenceId($basket[BasketDataBuilder::MERCHANT_REFERENCE])
            ->setShowBudget(false);

        if ($shippingInfo) {
            $transaction->setShippingInfo($shippingInfo);
        }

        $payment->setIntent($attributes[TransactionTypeBuilder::TRANSACTION_TYPE])
            ->setCustomer($customer)
            ->setTransaction($transaction)
            ->setRedirectUrls($attributes[PaymentUrlDataBuilder::PAYMENT_URLS]);

        return $payment->create($this->apiContext);
    }

    /**
     * @param array $attributes
     * @return Redirect
     */
    private function doRedirect(array $attributes): Redirect
    {
        $redirect = new Redirect();
        $transaction = new Transaction();

        $basket = $attributes[BasketDataBuilder::BASKET];
        $itemList = $attributes[FraudDataBuilder::ITEM_LIST];
        $fraudManagement = $attributes[FraudDataBuilder::FRAUD];
        $shippingInfo = $attributes[AddressDataBuilder::SHIPPING_INFO];

        if ($fraudManagement && $itemList) {
            $transaction->setItemList($itemList);
            $transaction->setFraudManagement($fraudManagement);
        }

        $transaction->setAmount($basket[BasketDataBuilder::AMOUNT])
            ->setDescription($basket[BasketDataBuilder::DESCRIPTION])
            ->setReferenceId($basket[BasketDataBuilder::MERCHANT_REFERENCE])
            ->setShowBudget(false);

        if ($shippingInfo) {
            $transaction->setShippingInfo($shippingInfo);
        }

        $redirect->setIntent($attributes[TransactionTypeBuilder::TRANSACTION_TYPE])
            ->setCustomer($attributes[CustomerDataBuilder::CUSTOMER])
            ->setTransaction($transaction)
            ->setRedirectUrls($attributes[PaymentUrlDataBuilder::PAYMENT_URLS]);

        return $redirect->setup($this->apiContext);
    }
}
