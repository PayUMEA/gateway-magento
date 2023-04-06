<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Adminhtml\System\Config\Source\Payment;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * class Environment
 * @package PayU\Gateway\Model\Adminhtml\System\Config\Source\Payment
 */
class Environment implements OptionSourceInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'sandbox',
                'label' => __('Sandbox')
            ],
            [
                'value' => 'live',
                'label' => __('Live')
            ]
        ];
    }
}
