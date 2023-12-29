<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

class DataFactory
{
    const AREA_FRONTEND = 'frontend';
    const AREA_BACKEND = 'adminhtml';

    /**
     * @var ObjectManagerInterface
     */
    protected ObjectManagerInterface $objectManager;

    /**
     * @var array
     */
    protected array $helperMap = [
        self::AREA_FRONTEND => 'PayU\EasyPlus\Helper\Data',
        self::AREA_BACKEND => 'PayU\EasyPlus\Helper\Backend\Data'
    ];

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create data helper
     *
     * @param string $area
     * @return \PayU\Gateway\Helper\Backend\Data|Data
     * @throws LocalizedException
     */
    public function create(string $area): \PayU\Gateway\Helper\Backend\Data|Data
    {
        if (!isset($this->helperMap[$area])) {
            throw new LocalizedException(__(sprintf('For this area <%s> no suitable helper', $area)));
        }

        return $this->objectManager->get($this->helperMap[$area]);
    }
}
