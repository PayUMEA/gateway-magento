<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\Gateway\Gateway\SubjectReader;
use PayUSdk\Model\Address;
use PayUSdk\Model\Phone;
use PayUSdk\Model\ShippingAddress;

/**
 * class AddressDataBuilder
 * @package PayU\Gateway\Gateway\Request
 */
class AddressDataBuilder implements BuilderInterface
{
    public const SHIPPING_INFO = 'shipping_info';

    /**
     * @param SubjectReader $subjectReader
     */
    public function __construct(private readonly SubjectReader $subjectReader)
    {
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
            $addressShipping = new Address();
            $addressShipping->setLine1($shippingAddress->getStreetLine1())
                ->setLine2($shippingAddress->getStreetLine2())
                ->setCity($shippingAddress->getCity())
                ->setState($shippingAddress->getRegionCode())
                ->setPostalCode($shippingAddress->getPostcode())
                ->setCountryCode($shippingAddress->getCountryId());

            $phone = new Phone(['national_number' => $shippingAddress->getTelephone()]);
            $shippingInfo = new ShippingAddress($addressShipping->toArray());
            $shippingInfo->setShippingId($payment->getOrder()->getShippingMethod())
                ->setPhone($phone)
                ->setEmail($shippingAddress->getEmail())
                ->setRecipientName(join(' ', [$shippingAddress->getFirstname(), $shippingAddress->getLastname()]))
                ->setShippingMethod($payment->getOrder()->getShippingDescription());
        }

        return [
            self::SHIPPING_INFO => $shippingInfo
        ];
    }
}
