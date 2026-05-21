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
use PayU\Gateway\Model\Payment\Method\MobileBanking;
use PayU\Gateway\Model\Payment\Method\MoreTyme;
use PayU\Gateway\Model\Payment\Method\Mpesa;
use PayU\Gateway\Model\Payment\Method\MtnMobile;
use PayU\Gateway\Model\Payment\Method\Payflex;
use PayU\Gateway\Model\Payment\Method\Rcs;
use PayU\Gateway\Model\Payment\Method\RcsPlc;
use PayU\Gateway\Model\Payment\Method\Tigopesa;
use PayU\Gateway\Model\Payment\Method\Ucount;

class ConfigProvider implements ConfigProviderInterface
{
    public const CREDIT_CARD_CODE = Creditcard::CODE;
    public const DISCOVERY_MILES_CODE = DiscoveryMiles::CODE;
    public const EBUCKS_CODE = Ebucks::CODE;
    public const EFT_PRO_CODE = EftPro::CODE;
    public const MOBICRED_CODE = Mobicred::CODE;
    public const PAYFLEX_CODE = Payflex::CODE;
    public const AIRTEL_MONEY_CODE = AirtelMoney::CODE;
    public const CAPITEC_PAY_CODE = CapitecPay::CODE;
    public const EQUITEL_CODE = Equitel::CODE;
    public const FASTA_CODE = Fasta::CODE;
    public const MORE_TYME_CODE = MoreTyme::CODE;
    public const UCOUNT_CODE = Ucount::CODE;
    public const TIGOPESA_CODE = Tigopesa::CODE;
    public const RCS_CODE = Rcs::CODE;
    public const RCS_PLC_CODE = RcsPlc::CODE;
    public const MPESA_CODE = Mpesa::CODE;
    public const MTN_MOBILE_CODE = MtnMobile::CODE;
    public const MOBILE_BANKING_CODE = MobileBanking::CODE;

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
        self::RCS_PLC_CODE,
        self::MPESA_CODE,
        self::MTN_MOBILE_CODE,
        self::MOBILE_BANKING_CODE
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
                $config['payment'][$code] = array_merge(
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
