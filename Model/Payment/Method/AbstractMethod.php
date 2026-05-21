<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment\Method;

use Magento\Payment\Model\Method\Adapter;

class AbstractMethod extends Adapter
{
    public const CODE = 'no_code';
    public const STATE_PENDING = 'pending';
}
