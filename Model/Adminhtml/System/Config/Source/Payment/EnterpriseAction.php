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
 * class EnterpriseAction
 * @package PayU\Gateway\Model\Adminhtml\System\Config\Source\Payment
 */
class EnterpriseAction implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected array $actions = [
        MethodInterface::ACTION_AUTHORIZE => 'Authorize',
        MethodInterface::ACTION_AUTHORIZE_CAPTURE => 'Authorize & Capture'
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
