<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Constants;

/**
 * class ResultCode
 * @package PayU\Gateway\Model\Constants
 */
enum ResultCode: string
{
    case POO5 = 'POO5';
    case EFTPRO_003 = 'EFTPRO_003';
    case EFTPRO_004 = 'EFTPRO_004';
    case TRIPLE_NINE = '999';
    case THREE_ZERO_FIVE = '305';
    case PEE_ZERO_FIFTEEN = 'P015';
    case PEE_ZERO_SEVENTY_NINE = 'P079';
    case NINE_NINE_ZERO = '990';
}
