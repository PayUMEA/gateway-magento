<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Gateway\SubjectReader;
use PayU\Gateway\Helper\Data;
use PayU\Model\TransactionUrl;

/**
 * class PaymentUrlDataBuilder
 * @package PayU\Gateway\Gateway\Request
 */
class PaymentUrlDataBuilder implements BuilderInterface
{
    public const PAYMENT_URLS = 'paymentUrl';

    /**
     * Constructor
     *
     * @param Config $config
     * @param Data $helper
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        private readonly Config $config,
        private readonly Data $helper,
        private readonly SubjectReader $subjectReader
    ) {
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws NoSuchEntityException
     */
    public function build(array $buildSubject): array
    {
        $storeId = $this->subjectReader->readStoreId($buildSubject);

        $cancelUrl = $this->config->getCancelUrl($storeId);
        $returnUrl = $this->config->getReturnUrl($storeId);
        $notifyUrl = $this->config->getNotifyUrl($storeId);

        $redirectUrls = new TransactionUrl();
        $redirectUrls->setNotificationUrl('https://468d-105-247-43-172.ngrok.io/' . $notifyUrl)
            ->setResponseUrl($this->helper->withBaseUrl($returnUrl))
            ->setCancelUrl($this->helper->withBaseUrl($cancelUrl));

        return [
            self::PAYMENT_URLS => $redirectUrls,
        ];
    }
}
