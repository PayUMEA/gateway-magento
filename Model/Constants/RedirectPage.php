<?php
/**
 * Copyright © 2024 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Constants;

/**
 * class RedirectPage
 * @package PayU\Gateway\Model\Constants
 */
enum RedirectPage: int
{
    case PENDING_PAGE = 1;
    case SUCCESS_PAGE = 2;
    case FAILED_PAGE = 3;
    case RETURN_CART = 4;
}
