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
    case NEW = 'NEW';
    case PROCESSING = 'PROCESSING';
    case SUCCESSFUL = 'SUCCESSFUL';
    case FAILED = 'FAILED';
    case TIMEOUT = 'TIMEOUT';
    case EXPIRED = 'EXPIRED';
    case AWAITING_PAYMENT = 'AWAITING_PAYMENT';
    const MAGENTO_ORDER_STATE_PENDING = 'pending';
    case REAL_TRANSACTION_ID_KEY = 'real_transaction_id';
}
