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
use PayU\Gateway\Model\Payment\Method\DiscoveryMiles;
use PayU\Gateway\Model\Payment\Method\Ebucks;
use PayU\Gateway\Model\Payment\Method\EftPro;
use PayU\Gateway\Model\Payment\Method\Mobicred;

/**
 * class ConfigProvider
 * @package PayU\Gateway\Model\Ui
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CREDIT_CARD_CODE = Creditcard::CODE;
    const DISCOVERY_MILES_CODE = DiscoveryMiles::CODE;
    const EBUCKS_CODE = Ebucks::CODE;
    const EFT_PRO_CODE = EftPro::CODE;
    const MOBICRED_CODE = Mobicred::CODE;

    /**
     * @var string[]
     */
    protected array $methodCodes = [
        self::CREDIT_CARD_CODE,
        self::DISCOVERY_MILES_CODE,
        self::EBUCKS_CODE,
        self::EFT_PRO_CODE,
        self::MOBICRED_CODE
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

        foreach ($this->methodCodes as $code) {
            $this->config->setMethodCode($code);
            $storeId = $this->session->getStoreId();
            $config['payment'][$code] = [
                'isActive' => $this->config->isActive($storeId),
                'isEnterprise' => $this->config->isEnterprise($storeId),
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
