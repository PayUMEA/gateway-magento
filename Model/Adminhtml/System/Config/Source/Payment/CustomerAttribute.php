<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Adminhtml\System\Config\Source\Payment;

use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * class CustomerAttribute
 * @package PayU\Gateway\Model\Adminhtml\System\Config\Source\Payment
 */
class CustomerAttribute implements OptionSourceInterface
{
    public function __construct(
        private readonly CustomerFactory $customerFactory
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        $attributesArrays = [];
        $customer = $this->customerFactory->create();
        $attributes = $customer->getAttributes();

        foreach ($attributes as $attributeCode) {
            if (!$attributeCode->getStoreLabel()) {
                continue;
            }

            $attributesArrays[] = [
                'label' => $attributeCode->getStoreLabel(),
                'value' => $attributeCode->getAttributeCode()
            ];
        }

        return $attributesArrays;
    }
}
