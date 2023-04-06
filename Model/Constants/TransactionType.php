<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Constants;

/**
 * class TransactionType
 * @package PayU\Gateway\Model\Constants
 */
enum TransactionType: string
{
    case PAYMENT = 'PAYMENT';
    case RESERVE = 'RESERVE';
    case CANCEL = 'RESERVE_CANCEL';
    case CREDIT = 'CREDIT';
    case FINALIZE = 'FINALIZE';
    case REGISTER_LINK = 'REGISTER_LINK';
    case ONCE_OFF_PAYMENT_AND_DEBIT_ORDER = 'ONCE_OFF_PAYMENT_AND_DEBIT_ORDER';
}
