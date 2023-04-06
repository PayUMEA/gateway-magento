<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use PayU\Api\Response;
use PayU\Gateway\Gateway\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
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
        $errorMessages = [];

        foreach ($this->getResponseValidators() as $validator) {
            $validationResult = $validator($response);

            if (!$validationResult[0]) {
                $isValid = $validationResult[0];
                $errorMessages = array_merge($errorMessages, $validationResult[1]);
            }
        }

        return $this->createResult($isValid, $errorMessages);
    }

    /**
     * @return array
     */
    protected function getResponseValidators(): array
    {
        return [
            function ($response) {
                return [
                    $response instanceof Resource
                    || $response instanceof Response,
                    [__('PayU Gateway error. No response!')]
                ];
            }
        ];
    }
}
