<?php

namespace PayU\Gateway\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Payment\Model\MethodInterface;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Gateway\SubjectReader;

class GlobalValidator extends AbstractValidator
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
        $storeId = $this->subjectReader->readStoreId($validationSubject);

        $isValid = $this->validConfigEnterpriseFlow($storeId);

        return $this->createResult($isValid[0], $isValid[1]);
    }

    /**
     * @param int $storeId
     * @return array
     */
    private function validConfigEnterpriseFlow(int $storeId): array
    {
        $enterprise = $this->config->isEnterprise($storeId);
        $paymentAction = $this->config->getTransactionType($storeId);

        return [
            ($enterprise && in_array(
                $paymentAction,
                [
                    MethodInterface::ACTION_AUTHORIZE,
                    MethodInterface::ACTION_AUTHORIZE_CAPTURE
                ]
            )) || (!$enterprise && in_array($paymentAction, [MethodInterface::ACTION_ORDER])),
            ['Payment method configuration error encountered. Contact merchant']];
    }
}
