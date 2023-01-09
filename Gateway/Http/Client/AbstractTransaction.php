<?php declare(strict_types=1);
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

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
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var Logger
     */
    protected Logger $customLogger;

    /**
     * @var PayUAdapterFactory
     */
    protected PayUAdapterFactory $adapterFactory;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param Logger $customLogger
     * @param PayUAdapterFactory $adapterFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Logger $customLogger,
        PayUAdapterFactory $adapterFactory
    ) {
        $this->logger = $logger;
        $this->customLogger = $customLogger;
        $this->adapterFactory = $adapterFactory;
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
