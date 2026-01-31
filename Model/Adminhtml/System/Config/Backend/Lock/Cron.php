<?php
/**
 * Copyright © 2025 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Adminhtml\System\Config\Backend\Lock;

use Magento\Framework\Exception\LocalizedException;

/**
 * Cron job configuration for currency
 *
 * @api
 * @since 100.0.2
 */
class Cron extends \Magento\Framework\App\Config\Value
{
    public const CRON_STRING_PATH = 'crontab/payu_gateway/jobs/payu_gateway_txn_lock/schedule/cron_expr';
    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection = null,
        array $data = []
    ) {
        $this->_configValueFactory = $configValueFactory;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * After save handler
     *
     * @return $this
     * @throws LocalizedException
     */
    public function afterSave()
    {
        $enabled = $this->getData('groups/payu_gateway_section/groups/payu_gateway/groups/payu_gateway_txn_lock/fields/enable/value');
        $time = $this->getData('groups/payu_gateway_section/groups/payu_gateway/groups/payu_gateway_txn_lock/fields/time/value');
        $frequency = $this->getData('groups/payu_gateway_section/groups/payu_gateway/groups/payu_gateway_txn_lock/fields/frequency/value');

        $frequencyWeekly = \Magento\Cron\Model\Config\Source\Frequency::CRON_WEEKLY;
        $frequencyMonthly = \Magento\Cron\Model\Config\Source\Frequency::CRON_MONTHLY;

        if ($enabled) {
            $cronExprArray = [
                (int)$time[1],                                 # Minute
                (int)$time[0],                                 # Hour
                $frequency == $frequencyMonthly ? '1' : '*',      # Day of the Month
                '*',                                              # Month of the Year
                $frequency == $frequencyWeekly ? '1' : '*',        # Day of the Week
            ];
            $cronExprString = join(' ', $cronExprArray);
        } else {
            $cronExprString = '';
        }

        try {
            /** @var $configValue \Magento\Framework\App\Config\ValueInterface */
            $configValue = $this->_configValueFactory->create();
            $configValue->load(self::CRON_STRING_PATH, 'path');
            $configValue->setValue($cronExprString)->setPath(self::CRON_STRING_PATH)->save();
        } catch (\Exception $e) {
            throw new LocalizedException(__('We can\'t save the Cron expression.'));
        }

        return parent::afterSave();
    }
}
