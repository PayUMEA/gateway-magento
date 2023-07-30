<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use PayU\Gateway\Gateway\SubjectReader;
use PayU\Model\Address;
use PayU\Model\Customer;
use PayU\Model\CustomerDetail;
use PayU\Model\Phone;

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

        $phone = new Phone(['national_number' => $billingAddress->getTelephone()]);
        $customerDetail = new CustomerDetail();
        $customerDetail->setFirstName($billingAddress->getFirstname())
            ->setLastName($billingAddress->getLastname())
            ->setEmail($billingAddress->getEmail())
            ->setPhone($phone)
            ->setCustomerId((string)$order->getCustomerId())
            ->setAddress($addressBilling)
            ->setIpAddress($order->getRemoteIp());

        $customerDetail = $this->setRegionalIdentification($order, $customerDetail);

        $customer = new Customer();
        $customer->setCustomerDetail($customerDetail);

        return [
            self::CUSTOMER => $customer
        ];
    }

    private function setRegionalIdentification(
        OrderAdapterInterface $order,
        CustomerDetail $customerDetail
    ): CustomerDetail {
        return $customerDetail;
    }
}
