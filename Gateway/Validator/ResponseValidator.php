<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Validator;

/**
 * class ResponseValidator
 * @package PayU\Gateway\Gateway\Validator
 */
class ResponseValidator extends DefaultResponseValidator
{
    /**
     * @return array
     */
    protected function getResponseValidators(): array
    {
        return array_merge(
            parent::getResponseValidators(),
            [
                function ($response) {
                    return [
                        $response->getReturn() &&
                        $response->getReturn()->getSuccessful() === true,
                        [__($response->getReturn()->getResultCode() ?? 'Transaction unsuccessful')]
                    ];
                }
            ]
        );
    }
}
