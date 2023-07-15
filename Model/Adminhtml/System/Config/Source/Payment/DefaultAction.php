<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Adminhtml\System\Config\Source\Payment;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\MethodInterface;

/**
 * class DefaultAction
 * @package PayU\Gateway\Model\Adminhtml\System\Config\Source\Payment
 */
class DefaultAction implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected array $actions = [
        MethodInterface::ACTION_ORDER => 'Order'
    ];

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $txnTypes = [];

        foreach ($this->actions as $key => $value) {
            $txnTypes[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $txnTypes;
    }
}
