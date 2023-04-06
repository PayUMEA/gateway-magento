<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Adminhtml\Source;

/**
 * class CcType
 * @package PayU\Gateway\Model\Adminhtml\Source
 */
class CcType extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * List of specific credit card types
     * @var array
     */
    private array $specificCardTypesList = [
        'CUP' => 'China Union Pay'
    ];

    /**
     * Allowed credit card types
     *
     * @return string[]
     */
    public function getAllowedTypes(): array
    {
        return ['VI', 'MC', 'AE', 'DI', 'JCB', 'MI', 'DN', 'CUP'];
    }

    /**
     * @return array
     */
    public function getCcTypeLabelMap(): array
    {
        return array_merge($this->specificCardTypesList, $this->_paymentConfig->getCcTypes());
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $allowed = $this->getAllowedTypes();
        $options = [];

        foreach ($this->getCcTypeLabelMap() as $code => $name) {
            if (in_array($code, $allowed)) {
                $options[] = ['value' => $code, 'label' => $name];
            }
        }

        return $options;
    }
}
