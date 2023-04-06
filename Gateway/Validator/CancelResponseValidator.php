<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Validator;

use PayU\Gateway\Model\Constants\ResultCode;

/**
 * class CancelResponseValidator
 * @package PayU\Gateway\Gateway\Validator
 */
class CancelResponseValidator extends DefaultResponseValidator
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
                        $response->getResultCode() === ResultCode::PEE_ZERO_FIFTEEN->value,
                        [__($response->getDisplayMessage() ?? 'Transaction unsuccessful')]
                    ];
                }
            ]
        );
    }
}
