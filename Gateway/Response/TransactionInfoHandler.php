<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use PayU\Gateway\Gateway\SubjectReader;
use PayU\Gateway\Model\Payment\TransferObjectFactory;

/**
 * class TransactionInfoHandler
 * @package PayU\Gateway\Gateway\Response
 */
class TransactionInfoHandler implements HandlerInterface
{
    /**
     * TransactionIdHandler constructor.
     * @param SubjectReader $subjectReader
     * @param TransferObjectFactory $transferFactory
     */
    public function __construct(
        private readonly SubjectReader $subjectReader,
        private readonly TransferObjectFactory $transferFactory
    ) {
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $responseObj = $this->subjectReader->readResponse($response);
        $transferObject = $this->transferFactory->create([
            'data' => ['txn' => json_decode($responseObj->toJson())]]
        );
        $payment = $paymentDO->getPayment();

        $transferObject->importTransactionInfo($payment);
    }
}
