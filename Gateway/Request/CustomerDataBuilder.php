<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use PayU\Api\Address;
use PayU\Api\Customer;
use PayU\Api\CustomerInfo;
use PayU\Gateway\Gateway\SubjectReader;

/**
 * class CustomerDataBuilder
 * @package PayU\Gateway\Gateway\Request
 */
class CustomerDataBuilder implements BuilderInterface
{
    public const CUSTOMER = 'customer';

    /**
     * Constructor
     *
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
        $billingAddress = $order->getBillingAddress();

        $addressBilling = new Address();
        $addressBilling->setLine1($billingAddress->getStreetLine1())
            ->setLine2($billingAddress->getStreetLine2())
            ->setCity($billingAddress->getCity())
            ->setState($billingAddress->getRegionCode())
            ->setPostalCode($billingAddress->getPostcode())
            ->setCountryCode($billingAddress->getCountryId());

        $customerInfo = new CustomerInfo();
        $customerInfo->setFirstName($billingAddress->getFirstname())
            ->setLastName($billingAddress->getLastname())
            ->setEmail($billingAddress->getEmail())
            ->setCountryOfResidence($billingAddress->getCountryId())
            ->setCountryCode('27')
            ->setPhone($billingAddress->getTelephone())
            ->setCustomerId($order->getCustomerId())
            ->setBillingAddress($addressBilling);

        $customer = new Customer();
        $customer->setCustomerInfo($customerInfo)
            ->setIPAddress($order->getRemoteIp());

        return [
            self::CUSTOMER => $customer
        ];
    }
}
