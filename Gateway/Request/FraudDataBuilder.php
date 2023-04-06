<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\Api\FmDetails;
use PayU\Api\Item;
use PayU\Api\ItemList;
use PayU\Gateway\Gateway\Config\Config;
use PayU\Gateway\Gateway\SubjectReader;
use PayU\Gateway\Helper\Data;

/**
 * class PaymentCardDetailsDataBuilder
 * @package PayU\Gateway\Gateway\Request
 */
class FraudDataBuilder implements BuilderInterface
{
    public const FRAUD = 'fraudManagement';
    public const ITEM_LIST = 'itemList';

    /**
     * @param Data $helper
     * @param Config $config
     * @param SubjectReader $subjectReader
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        private readonly Data $helper,
        private readonly Config $config,
        private readonly SubjectReader $subjectReader
    ) {
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws NoSuchEntityException
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        $fraudDetails = null;
        $itemList = null;
        $fraudEnabled = $this->config->hasFraudProtection((int)$order->getStoreId());

        if ($fraudEnabled) {
            $itemList = new ItemList();
            $orderItems = $order->getItems();

            foreach ($orderItems as $orderItem) {
                $item = new Item();
                $item->setDescription($orderItem->getName())
                    ->setSku($orderItem->getSku())
                    ->setQuantity($orderItem->getQtyOrdered())
                    ->setPrice($orderItem->getPrice());

                $itemList->addItem($item);
            }

            $fraudDetails = new FmDetails();
            $fraudDetails->setCheckFraudOverride(false)
                ->setMerchantWebsite($this->helper->withBaseUrl('/'))
                ->setPcFingerPrint($this->getFingerPrint());
        }

        return [
            self::FRAUD => $fraudDetails,
            self::ITEM_LIST => $itemList
        ];
    }

    /**
     * @return string
     */
    private function getFingerPrint(): string
    {
        return md5(implode('', $_SERVER));
    }
}
