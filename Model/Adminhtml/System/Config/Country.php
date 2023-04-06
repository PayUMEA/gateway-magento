<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Adminhtml\System\Config;

use Magento\Directory\Model\ResourceModel\Country\Collection;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * class Country
 * @package PayU\Gateway\Model\Adminhtml\System\Config
 */
class Country implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected array $options = [];

    /**
     * Countries not supported by Braintree
     * @var array
     */
    protected array $excludedCountries = [
        'MM',
        'IR',
        'SD',
        'BY',
        'CI',
        'CD',
        'CG',
        'IQ',
        'LR',
        'LB',
        'KP',
        'SL',
        'SY',
        'ZW',
        'AL',
        'BA',
        'MK',
        'ME',
        'RS'
    ];

    /**
     * @param Collection $countryCollection
     */
    public function __construct(private readonly Collection $countryCollection)
    {
    }

    /**
     * @param bool $isMultiselect
     * @return array
     */
    public function toOptionArray(bool $isMultiselect = false): array
    {
        if (!$this->options) {
            $this->options = $this->countryCollection
                ->addFieldToFilter('country_id', ['nin' => $this->getExcludedCountries()])
                ->loadData()
                ->toOptionArray(false);
        }

        $options = $this->options;
        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => __('--Please Select--')]);
        }

        return $options;
    }

    /**
     * If country is in list of restricted (not supported by Braintree)
     *
     * @param string $countryId
     * @return boolean
     */
    public function isCountryRestricted(string $countryId): bool
    {
        return in_array($countryId, $this->getExcludedCountries());
    }

    /**
     * Return list of excluded countries
     * @return array
     */
    private function getExcludedCountries(): array
    {
        return $this->excludedCountries;
    }
}
