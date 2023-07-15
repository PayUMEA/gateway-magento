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
use PayU\Api\Response;
use PayU\Api\ResponseInterface;
use PayU\Gateway\Gateway\SubjectReader;
use PayU\Resource;

/**
 * class DefaultResponseValidator
 * @package PayU\Gateway\Gateway\Validator
 */
class DefaultResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    protected SubjectReader $subjectReader;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = $this->subjectReader->readResponseObject($validationSubject);

        $isValid = true;
        $errorCodes = [];
        $errorMessages = [];

        foreach ($this->getResponseValidators() as $validator) {
            $validationResult = $validator($response);

            if (!$validationResult[0]) {
                $isValid = $validationResult[0];
                $errorMessages = array_merge($errorMessages, $validationResult[1]);
                $errorCodes = array_merge($errorCodes, $validationResult[2]);
            }
        }

        return $this->createResult($isValid, $errorMessages, $errorCodes);
    }

    /**
     * @return array
     */
    protected function getResponseValidators(): array
    {
        return [
            function ($response) {
                return [
                    $response instanceof ResponseInterface,
                    [__('PayU Gateway error. No response!')],
                    [997]
                ];
            }
        ];
    }
}
