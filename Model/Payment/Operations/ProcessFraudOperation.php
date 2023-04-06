<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Payment\Operations;

use Magento\Framework\DataObject;
use Magento\Framework\Simplexml\Element;
use Magento\Payment\Model\InfoInterface;

/**
 * class ProcessFraudOperation
 * @package PayU\Gateway\Model\Payment\Operations
 */
class ProcessFraudOperation
{
    /**
     * @param DataObject $fraudData
     */
    public function __construct(protected DataObject $fraudData)
    {
    }

    /**
     * @param InfoInterface $payment
     * @param DataObject $transactionInfo
     * @return void
     */
    public function fraud(InfoInterface $payment, DataObject $transactionInfo): void
    {
        $fraudDetailsResponse = $this->fetchFraudDetails($transactionInfo);
        $fraudData = $fraudDetailsResponse->getData();

        if (empty($fraudData)) {
            $payment->setIsFraudDetected(false);

            return;
        }

        $payment->setIsFraudDetected(true);
        $payment->setAdditionalInformation('fraud_details', $fraudData);
    }

    /**
     * @param DataObject $transactionInfo
     * @return DataObject
     */
    public function fetchFraudDetails(DataObject $transactionInfo): DataObject
    {
        if (empty($transactionInfo->getTransaction())) {
            return $this->fraudData;
        }

        $this->fraudData->setFdsFilterAction(
            $transactionInfo->getTransaction()->getFDSFilterAction()
        );
        $this->fraudData->setAvsResponse((string)$transactionInfo->getTransaction()->getAVSResponse());
        $this->fraudData->setCardCodeResponse((string)$transactionInfo->getTransaction()->getCardCodeResponse());
        $this->fraudData->setCavvResponse((string)$transactionInfo->getTransaction()->getCAVVResponse());
        $this->fraudData->setFraudFilters($this->getFraudFilters($transactionInfo->getTransaction()->getFDSFilters()));

        return $this->fraudData;
    }

    /**
     * Get fraud filters
     *
     * @param Element $fraudFilters
     * @return array
     */
    protected function getFraudFilters(Element $fraudFilters): array
    {
        $result = [];

        foreach ($fraudFilters->FDSFilter as $filer) {
            $result[] = [
                'name' => (string)$filer->name,
                'action' => (string)$filer->action
            ];
        }

        return $result;
    }
}
