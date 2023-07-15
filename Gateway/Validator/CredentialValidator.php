<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class CredentialValidator
 * @package PayU\Gateway\Gateway\Validator
 */
class CredentialValidator extends AbstractValidator
{
    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader $subjectReader
     * @param Config $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        private readonly SubjectReader $subjectReader,
        private readonly Config $config
    ) {
        parent::__construct($resultFactory);
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $paymentDO = $this->subjectReader->readPayment($validationSubject);
        $order = $paymentDO->getOrder();

        $isValid = $this->checkCredentials($order->getStoreId());

        return $this->createResult($isValid[0], $isValid[1]);
    }

    /**
     * @param int $storeId
     * @return array
     */
    private function checkCredentials(int $storeId): array
    {
        $safeKey = $this->config->getSafeKey($storeId);
        $username = $this->config->getApiUsername($storeId);
        $password = $this->config->getApiPassword($storeId);

        return [isset($safeKey, $username, $password), ['Payment method not available. Contact merchant.']];
    }
}
