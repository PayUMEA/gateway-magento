<?php declare(strict_types=1);
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace PayU\Gateway\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Config\Config as CoreConfig;

/**
 * class Config
 * @package PayU\Gateway\Gateway\Config
 */
class Config extends CoreConfig
{
    private const KEY_ACTIVE = 'active';
    private const KEY_DEBUG = 'debug';
    private const KEY_SAFE_KEY = 'safe_key';
    private const KEY_SHOW_BUDGET = 'budget';
    private const KEY_ENTERPRISE = 'enterprise';
    private const KEY_ENVIRONMENT = 'environment';
    private const KEY_API_USERNAME = 'api_username';
    private const KEY_API_PASSWORD = 'api_password';
    private const KEY_TRANSACTION_TYPE = 'payment_action';
    private const KEY_PAYMENT_METHODS = 'payment_methods';
    private const KEY_PAYMENT_RETURN_URL = 'payment_url/return_url';
    private const KEY_PAYMENT_CANCEL_URL = 'payment_url/cancel_url';
    private const KEY_PAYMENT_NOTIFY_URL = 'payment_url/notify_url';
    private const KEY_PAYMENT_REDIRECT_URL = 'payment_url/redirect_url';
    private const KEY_USE_CVV = 'useccv';
    private const KEY_CC_TYPES = 'cctypes';
    private const KEY_COUNTRY_CREDIT_CARD = 'country_creditcard';
    private const KEY_CC_TYPES_MAPPER = 'cctypes_payu_gateway_mapper';
    private const FRAUD_PROTECTION = 'fraudprotection';

    /**
     * Braintree config constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param null|string $methodCode
     * @param string $pathPattern
     * @param Json|null $serializer
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected EncryptorInterface $encryptor,
        protected $methodCode = null,
        protected string $pathPattern = self::DEFAULT_PATH_PATTERN,
        protected ?Json $serializer = null
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);

        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(Json::class);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isActive(int|null $storeId = null): bool
    {
        return (bool)$this->getValue(self::KEY_ACTIVE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function debug(int|null $storeId = null): bool
    {
        return (bool)$this->getValue(self::KEY_DEBUG, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getSafeKey(int|null $storeId = null): string
    {
        return $this->getValue(Config::KEY_SAFE_KEY, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isBudgetAllowed(int|null $storeId = null): bool
    {
        return (bool)$this->getValue(Config::KEY_SHOW_BUDGET, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnterprise(int|null $storeId = null): bool
    {
        return (bool)$this->getValue(Config::KEY_ENTERPRISE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getEnvironment(int|null $storeId = null): string
    {
        return $this->getValue(self::KEY_ENVIRONMENT, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getApiUsername(int|null $storeId = null): string
    {
        return $this->getValue(self::KEY_API_USERNAME, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getApiPassword(int|null $storeId = null): string
    {
        return $this->getValue(self::KEY_API_PASSWORD, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getTransactionType(int|null $storeId = null): string
    {
        return $this->getValue(Config::KEY_TRANSACTION_TYPE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getSupportedPaymentMethods(int|null $storeId = null): string
    {
        return $this->getValue(Config::KEY_PAYMENT_METHODS, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isSecure3ds(int|null $storeId = null): bool
    {
        return (bool)$this->getValue(Config::KEY_PAYMENT_METHODS, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getNotifyUrl(int|null $storeId = null): string
    {
        return $this->getValue(Config::KEY_PAYMENT_NOTIFY_URL, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getReturnUrl(int|null $storeId = null): string
    {
        return $this->getValue(Config::KEY_PAYMENT_RETURN_URL, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getCancelUrl(int|null $storeId = null): string
    {
        return $this->getValue(Config::KEY_PAYMENT_CANCEL_URL, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getRedirectUrl(int|null $storeId = null): string
    {
        return $this->getValue(Config::KEY_PAYMENT_REDIRECT_URL, $storeId);
    }

    /**
     * Return the country specific card type config
     *
     * @param int|null $storeId
     * @return array
     */
    public function getCountrySpecificCardTypeConfig(int|null $storeId = null): array
    {
        $countryCardTypes = $this->getValue(self::KEY_COUNTRY_CREDIT_CARD, $storeId);

        if (!$countryCardTypes) {
            return [];
        }

        $countryCardTypes = $this->serializer->unserialize($countryCardTypes);

        return is_array($countryCardTypes) ? $countryCardTypes : [];
    }

    /**
     * Retrieve available credit card types
     *
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableCardTypes(int|null $storeId = null): array
    {
        $ccTypes = $this->getValue(self::KEY_CC_TYPES, $storeId);

        return !empty($ccTypes) ? explode(',', $ccTypes) : [];
    }

    /**
     * Retrieve mapper between Magento and Braintree card types
     *
     * @return array
     */
    public function getCcTypesMapper(): array
    {
        $result = json_decode(
            $this->getValue(self::KEY_CC_TYPES_MAPPER),
            true
        );

        return is_array($result) ? $result : [];
    }

    /**
     * Gets list of card types available for country.
     *
     * @param string $country
     * @param int|null $storeId
     * @return array
     */
    public function getCountryAvailableCardTypes(string $country, int|null $storeId = null): array
    {
        $types = $this->getCountrySpecificCardTypeConfig($storeId);

        return (!empty($types[$country])) ? $types[$country] : [];
    }

    /**
     * Checks if cvv field is enabled.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCvvEnabled(int|null $storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_USE_CVV, $storeId);
    }

    /**
     * Checks if fraud protection is enabled.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function hasFraudProtection(int|null $storeId = null): bool
    {
        return (bool) $this->getValue(self::FRAUD_PROTECTION, $storeId);
    }
}
