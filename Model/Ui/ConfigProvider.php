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
use PayU\Gateway\Model\Payment\Method\AirtelMoney;
use PayU\Gateway\Model\Payment\Method\CapitecPay;
use PayU\Gateway\Model\Payment\Method\Creditcard;
use PayU\Gateway\Model\Payment\Method\DiscoveryMiles;
use PayU\Gateway\Model\Payment\Method\Ebucks;
use PayU\Gateway\Model\Payment\Method\EftPro;
use PayU\Gateway\Model\Payment\Method\Equitel;
use PayU\Gateway\Model\Payment\Method\Fasta;
use PayU\Gateway\Model\Payment\Method\Mobicred;
use PayU\Gateway\Model\Payment\Method\MoreTyme;
use PayU\Gateway\Model\Payment\Method\Mpesa;
use PayU\Gateway\Model\Payment\Method\Payflex;
use PayU\Gateway\Model\Payment\Method\Rcs;
use PayU\Gateway\Model\Payment\Method\RcsPlc;
use PayU\Gateway\Model\Payment\Method\Tigopesa;
use PayU\Gateway\Model\Payment\Method\Ucount;

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
    const PAYFLEX_CODE = Payflex::CODE;
    const AIRTEL_MONEY_CODE = AirtelMoney::CODE;
    const CAPITEC_PAY_CODE = CapitecPay::CODE;
    const EQUITEL_CODE = Equitel::CODE;
    const FASTA_CODE = Fasta::CODE;
    const MORE_TYME_CODE = MoreTyme::CODE;
    const UCOUNT_CODE = Ucount::CODE;
    const TIGOPESA_CODE = Tigopesa::CODE;
    const RCS_CODE = Rcs::CODE;
    const RCS_CODE_PLC = RcsPlc::CODE;
    const MPESA_CODE = Mpesa::CODE;

    /**
     * @var string[]
     */
    protected array $methodCodes = [
        self::CREDIT_CARD_CODE,
        self::DISCOVERY_MILES_CODE,
        self::EBUCKS_CODE,
        self::EFT_PRO_CODE,
        self::MOBICRED_CODE,
        self::PAYFLEX_CODE,
        self::AIRTEL_MONEY_CODE,
        self::CAPITEC_PAY_CODE,
        self::EQUITEL_CODE,
        self::FASTA_CODE,
        self::MORE_TYME_CODE,
        self::UCOUNT_CODE,
        self::TIGOPESA_CODE,
        self::RCS_CODE,
        self::RCS_CODE_PLC,
        self::MPESA_CODE
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
                'imageSrc' => $this->getPaymentMethodImageUrl($code),
                'redirectUrl' => $this->helper->withBaseUrl($this->config->getRedirectUrl()),
            ];

            if ($code === self::CREDIT_CARD_CODE) {
                array_merge(
                    $config['payment'][$code],
                    [
                        'ccTypesMapper' => $this->config->getCcTypesMapper(),
                        'availableCardTypes' => $this->config->getAvailableCardTypes($storeId),
                        'countrySpecificCardTypes' => $this->config->getCountrySpecificCardTypeConfig($storeId),
                    ]
                );
            }
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
