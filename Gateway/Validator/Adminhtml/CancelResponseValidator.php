<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Validator\Adminhtml;

use PayU\Gateway\Gateway\Validator\DefaultResponseValidator;

/**
 * class CancelResponseValidator
 * @package PayU\Gateway\Gateway\Validator\Adminhtml
 */
class CancelResponseValidator extends DefaultResponseValidator
{
    /**
     * @return array
     */
    protected function getResponseValidators(): array
    {
        return [];
    }
}
