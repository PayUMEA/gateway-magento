<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Adminhtml\System\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

/**
 * class PendingPayment
 * @package PayU\Gateway\Model\Adminhtml\System\Config\Source\Order\Status
 */
class PendingPayment extends Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses = [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT];
}
