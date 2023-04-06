<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Asset\Repository;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Helper\Data;
use PayU\Gateway\Model\Payment\Method\Creditcard;

/**
 * class ConfigProvider
 * @package PayU\Gateway\Model\Ui
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CREDIT_CARD_CODE = Creditcard::CODE;

    /**
     * @var string[]
     */
    protected array $methodCodes = [
        self::CREDIT_CARD_CODE
    ];

    /**
     * Constructor
     *
     * @param Config $config
     * @param Data $helper
     * @param SessionManagerInterface $session
     * @param Repository $assetRepo
     */
    public function __construct(
        private readonly Config $config,
        private readonly Data $helper,
        private readonly SessionManagerInterface $session,
        private readonly Repository $assetRepo
    ) {
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $config = [];
        $storeId = $this->session->getStoreId();
        $isActive = $this->config->isActive($storeId);
        $isEnterprise = $this->config->isEnterprise($storeId);

        foreach ($this->methodCodes as $code) {
            $config['payment'][$code] = [
                'isActive' => $isActive,
                'isEnterprise' => $isEnterprise,
                'ccTypesMapper' => $this->config->getCcTypesMapper(),
                'imageSrc' => $this->getPaymentMethodImageUrl($code),
                'availableCardTypes' => $this->config->getAvailableCardTypes($storeId),
                'redirectUrl' => $this->helper->withBaseUrl($this->config->getRedirectUrl()),
                'countrySpecificCardTypes' => $this->config->getCountrySpecificCardTypeConfig($storeId),
            ];
        }

        return $config;
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getPaymentMethodImageUrl(string $code): string
    {
        return $this->assetRepo->getUrl('PayU_Gateway::images/' . $code . '.png');
    }
}
