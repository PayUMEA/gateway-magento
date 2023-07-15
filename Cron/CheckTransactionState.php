<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Cron;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use PayU\Api\ResponseInterface;
use PayU\Gateway\Model\Adapter\PayUAdapter;
use PayU\Gateway\Model\Adapter\PayUAdapterFactory;
use PayU\Gateway\Model\Payment\Operations\CreateInvoiceOperation;
use PayU\Gateway\Model\Payment\Operations\TransactionUpdateOperation;
use PayU\Gateway\Model\Payment\TransferObject;
use Psr\Log\LoggerInterface;

/**
 * class CheckTransactionState
 * @package PayU\Gateway\Cron
 */
class CheckTransactionState
{
    private const CONFIG_PATTERN = 'payment/%s/%s';
    private const CRON_CONFIG_PATTERN = 'payment/payu_gateway_cron/%s';

    /**
     * @var string|null
     */
    protected ?string $code = null;

    /**
     * @var string
     */
    protected string $processId = '';

    /**
     * @var PayUAdapter
     */
    protected payUAdapter $payUAdapter;

    /**
     * CheckTransactionState constructor.
     * @param LoggerInterface $logger
     * @param PayUAdapterFactory $apiFactory
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param CollectionFactory $orderCollectionFactory
     * @param CreateInvoiceOperation $invoiceOperation
     * @param TransactionUpdateOperation $transactionUpdateOperation
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PayUAdapterFactory $apiFactory,
        private readonly EncryptorInterface $encryptor,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CollectionFactory $orderCollectionFactory,
        private readonly CreateInvoiceOperation $invoiceOperation,
        private readonly TransactionUpdateOperation $transactionUpdateOperation
    ) {
        $this->payUAdapter = $apiFactory->create();
    }

    /**
     * @param ResponseInterface $response
     * @param OrderInterface $order
     * @param InfoInterface $payment
     * @return void
     * @throws LocalizedException
     */
    public function processReturn(ResponseInterface $response, OrderInterface $order, InfoInterface $payment): void
    {
        $data = $response->toArray();
        $transactionNotes = "<strong>-----PAYU GATEWAY CRON: STATUS CHECKED ---</strong><br />";

        if (!isset($data['resultCode']) || (in_array($data['resultCode'], ['POO5', 'EFTPRO_003', '999', '305']))) {
            $this->logger->info(
                "PAYU GATEWAY CRON: ($this->processId) Result code - {$data['resultCode']}. Skip order processing"
            );
            $this->logger->info("PayU txn data: " . PHP_EOL . json_encode($data));
        }

        if (
            !isset($data["transactionState"])
            || (
                !in_array(
                    $data['transactionState'],
                    ['PROCESSING', 'SUCCESSFUL', 'AWAITING_PAYMENT', 'FAILED', 'TIMEOUT', 'EXPIRED']
                )
            )
        ) {
            $this->logger->info("PAYU GATEWAY CRON: ($this->processId) Invalid  transaction state");
            $this->logger->info(json_encode($data));

            return;
        }

        $transactionNotes .= "PayU Reference: " . $data["payUReference"] . "<br />";
        $transactionNotes .= "PayU Transaction state: " . $data["transactionState"] . "<br /><br />";

        switch ($data['transactionState']) {
            case 'SUCCESSFUL':
                $this->invoiceOperation->invoice($order);
                $this->transactionUpdateOperation->update($order, $payment, new TransferObject($data));
                break;
            case 'FAILED':
            case 'TIMEOUT':
            case 'EXPIRED':
                $order->cancel();
                $this->logger->info(
                    "PAYU GATEWAY CRON: ({$order->getEntityId()}) Transaction state prevents processing order"
                );
                break;
        }

        $order->addCommentToStatusHistory($transactionNotes);
        $this->orderRepository->save($order);
    }

    /**
     * @return Collection
     */
    public function getOrderCollection(): Collection
    {
        return $this->orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter(
                'status',
                ['in' => explode(',', $this->getCronConfigData('order_status'))]
            );
    }

    /**
     * @return void
     * @throws LocalizedException|Exception
     */
    public function execute(): void
    {
        $cronDisabled = (bool)$this->getCronConfigData('bypass_cron');

        if ($cronDisabled) {
            $this->logger->info("PAYU GATEWAY CRON: Disabled");

            return;
        }

        $processId = uniqid();
        $this->processId = $processId;

        $this->logger->info("PAYU GATEWAY CRON: Started, PID: $processId");

        $orders = $this->getOrderCollection();

        foreach ($orders->getItems() as $order) {
            $payment = $order->getPayment();
            $additionalInfo = $payment->getAdditionalInformation();
            $transactionId = $payment->getLastTransId();
            $code = $payment->getData('method');
            $this->code = $code;

            $id = $order->getIncrementId();
            $this->logger->info("($processId) Checking: $id");

            if (!str_contains($code, 'payu_gateway')) {
                $this->logger->info("PAYU GATEWAY CRON: ($processId) Not a PayU payment method");

                continue;
            }

            if (isset($additionalInfo["fraud_details"])) {
                if ($additionalInfo["fraud_details"]["return"]["transactionState"] === 'SUCCESSFUL') {
                    $this->logger->info("PAYU GATEWAY CRON: ($processId) ($id) Already successful");

                    continue;
                }

                $payUReference = $additionalInfo["fraud_details"]["return"]["payUReference"];
            } else {
                $payUReference = $additionalInfo["payUReference"] ?? $transactionId;
            }

            if (!isset($payUReference)) {
                $this->logger->info("PAYU GATEWAY CRON: ($processId) No PayU reference");

                continue;
            }

            if (!$this->shouldDoCheck($order)) {
                $this->logger->info("PAYU GATEWAY CRON: ($processId) ($id) Check delayed");

                continue;
            }

            $this->logger->info("PAYU GATEWAY CRON: ($processId) ($id) Doing Check");

            $order = $this->orderRepository->get($order->getId());

            if ($order->getState() == Order::STATE_PROCESSING) {
                $this->logger->info(
                    "PAYU GATEWAY CRON: Order completed, skip processing. Order id = " . $order->getId()
                );

                continue;
            }

            if ($order->hasInvoices()) {
                $this->logger->info(
                    "PAYU GATEWAY CRON: ($processId) Already invoiced, skip processing. order id = "
                    . $order->getId()
                );

                continue;
            }

            $result = $this->payUAdapter->search($payUReference);

            try {
                $this->processReturn($result, $order, $payment);
            } catch (Exception $exception) {
                $this->logger->info('PAYU GATEWAY CRON: ' . $exception->getMessage());
                $this->logger->info($result->toJSON());
            }

            $order->setUpdatedAt(null);
            $this->orderRepository->save($order);
        }

        $this->logger->info("PAYU GATEWAY CRON: Ended, PID: $processId");
    }

    /**
     * @param $order
     * @return bool
     */
    protected function shouldDoCheck($order): bool
    {
        $createdAt = strtotime($order->getCreatedAt());
        $updatedAt = strtotime($order->getUpdatedAt());

        $now = time();

        $minutesCreated = (int) ceil(($now - $createdAt) / 60);
        $minutesUpdated = $minutesCreated - (int) ceil(($now - $updatedAt) / 60);

        $cronDelay = $this->getCronConfigData('cron_delay');

        if (empty($cronDelay)) {
            $cronDelay = "5";
        }

        $this->logger->info(
            "PAYU GATEWAY CRON: ($this->processId) Minutes created: $minutesCreated - Delay: $cronDelay mins"
        );
        $this->logger->info(
            "PAYU GATEWAY CRON: ($this->processId) Minutes updated: $minutesUpdated - Delay: $cronDelay mins"
        );

        $minutesCreated = $minutesCreated - $cronDelay;
        $minutesUpdated = $minutesUpdated - $cronDelay;

        $ranges = [];
        $ranges[] = [1, 4];
        $ranges[] = [5, 9];
        $ranges[] = [10, 19];
        $ranges[] = [20, 29];
        $ranges[] = [30, 59];
        $ranges[] = [(1 * 60), (2 * 60) - 1];
        $ranges[] = [(2 * 60), (3 * 60) - 1];
        $ranges[] = [(3 * 60), (6 * 60) - 1];
        $ranges[] = [(6 * 60), (12 * 60) - 1];
        $ranges[] = [(12 * 60), (24 * 60) - 1];

        for ($i = 1; $i <= 31; $i++) {
            $ii = $i * 24;
            $ranges[] = [($ii * 60), ($ii * 60) - 1];
        }

        foreach ($ranges as $v) {
            if ((
                ($v[0] <= $minutesCreated) && ($minutesCreated <= $v[1])
            )  && (!(($v[0]  <= $minutesUpdated) && ($minutesUpdated <= $v[1])))
            ) {
                return true;
            }
        }

        if (((744 <= $minutesCreated))  && (!((744<= $minutesUpdated)))) {
            return true;
        }

        $this->logger->info("PAYU GATEWAY CRON: ($this->processId) Check Not Needed");

        return false;
    }

    /**
     * @param $key
     * @param $storeId
     * @return mixed|string
     */
    public function getValue($key, $storeId = null): mixed
    {
        if (in_array($key, ['safe_key', 'api_password'])) {
            return $this->encryptor->decrypt($this->getConfigData($key, $storeId));
        }

        return $this->getConfigData($key, $storeId);
    }

    /**
     * @param string $field
     * @param $storeId
     * @return mixed
     */
    public function getConfigData(string $field, $storeId = null): mixed
    {
        $path = sprintf(self::CONFIG_PATTERN, $this->code, $field);

        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param string $field
     * @param $storeId
     * @return mixed
     */
    public function getCronConfigData(string $field, $storeId = null): mixed
    {
        $path = sprintf(self::CRON_CONFIG_PATTERN, $field);

        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
