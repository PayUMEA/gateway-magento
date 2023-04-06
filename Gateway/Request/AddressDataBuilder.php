<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\OrderFactory;
use PayU\Api\ShippingAddress;
use PayU\Api\ShippingInfo;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class AddressDataBuilder
 * @package PayU\Gateway\Gateway\Request
 */
class AddressDataBuilder implements BuilderInterface
{
    public const SHIPPING_INFO = 'shipping_info';

    /**
     * @param OrderFactory $orderFactory
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        private readonly OrderFactory $orderFactory,
        private readonly SubjectReader $subjectReader
    ) {
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        $shippingInfo = null;

        $shippingAddress = $order->getShippingAddress();

        if ($shippingAddress) {
            $shippingInfo = new ShippingInfo();
            $addressShipping = new ShippingAddress();
            $addressShipping->setLine1($shippingAddress->getStreetLine1())
                ->setLine2($shippingAddress->getStreetLine2())
                ->setCity($shippingAddress->getCity())
                ->setState($shippingAddress->getRegionCode())
                ->setPostalCode($shippingAddress->getPostcode())
                ->setCountryCode($shippingAddress->getCountryId());

            $shippingInfo->setId($payment->getOrder()->getShippingMethod())
                ->setFirstName($shippingAddress->getFirstname())
                ->setLastName($shippingAddress->getLastname())
                ->setEmail($shippingAddress->getEmail())
                ->setPhone($shippingAddress->getTelephone())
                ->setMethod($payment->getOrder()->getShippingDescription())
                ->setShippingAddress($addressShipping);
        }

        return [
            self::SHIPPING_INFO => $shippingInfo
        ];
    }
}
