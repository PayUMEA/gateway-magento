<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Gateway\Http\Client;

use Exception;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use PayU\Gateway\Model\Adapter\PayUAdapterFactory;
use Psr\Log\LoggerInterface;

/**
 * class AbstractTransaction
 * @package PayU\Gateway\Gateway\Http\Client
 */
abstract class AbstractTransaction implements ClientInterface
{
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param Logger $customLogger
     * @param PayUAdapterFactory $adapterFactory
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected Logger $customLogger,
        protected PayUAdapterFactory $adapterFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $data = $transferObject->getBody();
        $log = [
            'request' => $data,
            'client' => static::class
        ];
        $response['object'] = [];

        try {
            $response['object'] = $this->process($data);
        } catch (Exception $e) {
            $message = __($e->getMessage() ?: 'Sorry, but something went wrong');
            $this->logger->critical($message);
            throw new ClientException($message);
        } finally {
            $log['response'] = (array) $response['object'];
            $this->customLogger->debug($log);
        }

        return $response;
    }

    /**
     * Process http request
     * @param array $data
     * @return mixed
     */
    abstract protected function process(array $data): mixed;
}
