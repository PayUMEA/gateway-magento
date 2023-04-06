<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Validator;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class CredentialValidator
 * @package PayU\Gateway\Gateway\Validator
 */
class CredentialValidator extends DefaultResponseValidator
{
    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader $subjectReader
     * @param Config $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader,
        private readonly Config $config
    ) {
        parent::__construct($resultFactory, $subjectReader);
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $isValid = false;
        $paymentDO = $this->subjectReader->readPayment($validationSubject);
        $order = $paymentDO->getOrder();

        $safeKey = $this->config->getSafeKey($order->getStoreId());
        $username = $this->config->getApiUsername($order->getStoreId());
        $password = $this->config->getApiPassword($order->getStoreId());

        if (isset($safeKey, $username, $password)) {
            $isValid = true;
        }

        return $this->createResult($isValid);
    }
}
