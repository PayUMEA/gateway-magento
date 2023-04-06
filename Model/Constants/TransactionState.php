<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Constants;

/**
 * class TransactionState
 * @package PayU\Gateway\Model\Constants
 */
enum TransactionState: string
{
    case PROCESSING = 'PROCESSING';
    case SUCCESSFUL = 'SUCCESSFUL';
    case FAILED = 'FAILED';
    case TIMEOUT = 'TIMEOUT';
    case EXPIRED = 'EXPIRED';
    case AWAITING_PAYMENT = 'AWAITING_PAYMENT';
    case REAL_TRANSACTION_ID_KEY = 'real_transaction_id';
}
