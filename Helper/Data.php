<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * class Data
 * @package PayU\Gateway\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected Context $context,
        protected OrderFactory $orderFactory,
        protected StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    /**
     * Set secure url checkout is secure for current store.
     *
     * @param string $route
     * @param array $params
     * @return string
     * @throws NoSuchEntityException
     */
    protected function _getUrl($route, $params = []): string
    {
        $params['_type'] = UrlInterface::URL_TYPE_LINK;

        if (isset($params['is_secure'])) {
            $params['_secure'] = (bool)$params['is_secure'];
        } elseif ($this->storeManager->getStore()->isCurrentlySecure()) {
            $params['_secure'] = true;
        }

        return parent::_getUrl($route, $params);
    }

    /**
     * Get url with store base url
     *
     * @param string $url
     * @param int|string|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function withBaseUrl(string $url, int|string $storeId = null): string
    {
        $baseUrl = $this->storeManager->getStore($storeId)
            ->getBaseUrl(UrlInterface::URL_TYPE_LINK);

        return $baseUrl . $url;
    }

    /**
     * Gateway error response wrapper
     *
     * @param string $text
     * @return Phrase
     */
    public function wrapGatewayError(string $text): Phrase
    {
        return __('Gateway error: %1', $text);
    }
}
