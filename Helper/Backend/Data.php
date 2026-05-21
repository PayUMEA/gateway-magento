<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Helper\Backend;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use PayU\Gateway\Helper\Data as FrontendDataHelper;

/**
 * PayU EasyPlus Backend Data Helper
 */
class Data extends FrontendDataHelper
{
    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param OrderFactory $orderFactory
     * @param UrlInterface $backendUrl
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        OrderFactory $orderFactory,
        UrlInterface $backendUrl
    ) {
        $this->_urlBuilder = $backendUrl;

        parent::__construct($context, $orderFactory, $storeManager);
    }

    /**
     * Return URL for admin area
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    protected function _getUrl($route, $params = []): string
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }

    /**
     * Retrieve place order url in admin
     *
     * @return  string
     */
    public function getPlaceOrderAdminUrl(): string
    {
        return $this->_getUrl('adminhtml/payu_easyplus_payment/place', []);
    }

    /**
     * Retrieve place order url
     *
     * @param array $params
     * @return  string
     */
    public function getSuccessOrderUrl(array $params): string
    {
        $param = [];
        $route = 'sales/order/view';
        $order = $this->orderFactory->create()->loadByIncrementId($params['x_invoice_num']);
        $param['order_id'] = $order->getId();

        return $this->_getUrl($route, $param);
    }

    /**
     * Retrieve redirect iframe url
     *
     * @param array $params
     * @return string
     */
    public function getRedirectIframeUrl($params): string
    {
        return $this->_getUrl('adminhtml/payu_gateway_payment/redirect', $params);
    }

    /**
     * Get direct post relay url
     *
     * @param int|string|null $storeId
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getRelayUrl(int|string|null $storeId = null): string
    {
        $defaultStore = $this->storeManager->getDefaultStoreView();

        if (!$defaultStore) {
            $allStores = $this->storeManager->getStores();

            if (isset($allStores[0])) {
                $defaultStore = $allStores[0];
            }
        }

        $baseUrl = $defaultStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);

        return $baseUrl . 'payu/gateway_payment/backendResponse';
    }
}
