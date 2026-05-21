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
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PayU\Gateway\Model\Adapter\PayUAdapterFactory;
use PayU\Gateway\Model\Constants\TransactionState;
use PayU\Gateway\Model\Payment\Operations\AcceptPaymentOperation;
use PayU\Gateway\Model\Payment\Operations\DenyPaymentOperation;
use Psr\Log\LoggerInterface;

class CheckTransactionStatus
{
    private const CRON_CONFIG_PATTERN = 'payu_gateway/txn_status/%s';

    /**
     * @var string|null
     */
    protected ?string $code = null;

    /**
     * @var string
     */
    protected string $processId = '';

    /**
     * @var string
     */
    protected string $processClass = '';

    /**
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param CollectionFactory $orderCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param AcceptPaymentOperation $acceptPaymentOperation
     * @param DenyPaymentOperation $denyPaymentOperation
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CollectionFactory $orderCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly AcceptPaymentOperation $acceptPaymentOperation,
        private readonly DenyPaymentOperation $denyPaymentOperation
    ) {
    }

    /**
     * @param OrderInterface|Order $order
     * @param InfoInterface $payment
     * @return void
     * @throws LocalizedException
     */
    public function processReturn(OrderInterface|Order $order, InfoInterface $payment): void
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $transactionAdditionalInfo = $payment->getTransactionAdditionalInfo();
        $transactionInfo = $transactionAdditionalInfo['transactionInfo'] ?? null;

        if (!$transactionInfo) {
            return;
        }

        $resultCode = $transactionInfo->getResultCode();

        if (in_array($resultCode, ['POO5', 'EFTPRO_003', '999', '305'])) {
            $this->logger->info(
                "PAYU GATEWAY TXN STATUS CRON: ($this->processId) Result code - $resultCode. Skip order processing"
            );

            return;
        }

        $transactionState = $transactionInfo->getTransactionState();
        $txnState = TransactionState::tryFrom($transactionState);

        if (!in_array(
            $txnState,
            TransactionState::cases()
        )
        ) {
            $this->logger->info(
                sprintf(
                    "PAYU GATEWAY TXN STATUS CRON: (%s) Invalid  transaction state: %s",
                    $this->processId,
                    $transactionState
                )
            );

            return;
        }

        $txnId = $transactionInfo->getTranxId();
        $totalDue = $transactionInfo->getTotalDue();
        $totalPaid = $transactionInfo->getTotalCaptured();

        $comment = "<strong>-----PAYU GATEWAY TXN STATUS CRON: STATUS CHECK-----</strong><br />";
        $comment .= "Order Amount: " . $totalDue . "<br />";
        $comment .= "Amount Paid: " . $totalPaid . "<br />";
        $comment .= "Merchant Reference : " . $transactionInfo->getMerchantReference() . "<br />";
        $comment .= "Transaction Type: " . $transactionInfo->getTransactionType() . "<br />";
        $comment .= "PayU Reference: " . $txnId . "<br />";
        $comment .= "Payment Status: " . $transactionInfo->getTransactionState() . "<br /><br />";

        if ($transactionInfo->hasPaymentMethod()) {
            $paymentMethods = $transactionInfo->getPaymentMethods();
            $comment .= "<strong>Payment Method Details:</strong>";

            if (!is_array($paymentMethods)) {
                $paymentMethods = [$paymentMethods];
            }

            foreach ($paymentMethods as $type => $paymentMethod) {
                $comment .= "<br />===Payment Method " . $type + 1 . "===";
                foreach ($paymentMethod as $key => $value) {
                    $comment .= "<br />&nbsp;&nbsp;=> " . $key . ": " . $value;
                }
                $comment .= '<br />';
            }
        }

        switch ($transactionState) {
            case 'SUCCESSFUL':
                $this->acceptPaymentOperation->accept($payment, $comment);
                break;
            case 'FAILED':
            case 'TIMEOUT':
            case 'EXPIRED':
                $this->denyPaymentOperation->deny($payment, $comment);
                break;
            default:
                $this->logger->info(
                    sprintf(
                        'PAYU GATEWAY TXN STATUS CRON: Unprocessable order (%s), Status (%s)',
                        $order->getIncrementId(),
                        $transactionState
                    )
                );
        }
    }

    /**
     * @return Collection
     */
    public function getOrderCollection(string $storeId): Collection
    {
        return $this->orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter(
                'status',
                ['in' => explode(',', $this->getCronConfigData('order_status'))]
            )
            ->setOrder(
                'created_at',
                'asc'
            );
    }

    /**
     * @return void
     * @throws LocalizedException|Exception
     */
    public function execute(): void
    {
        $processId = uniqid();
        $this->processId = $processId;
        $this->processClass = self::class;

        $this->logger->info("PAYU GATEWAY TXN STATUS CRON: Started, PID: $processId");

        $websites = $this->storeManager->getWebsites();

        foreach ($websites as $website) {
            /** @var \Magento\Store\Api\Data\WebsiteInterface $website */
            $websiteStores = $this->storeManager->getStores(false);

            foreach ($websiteStores as $store) {
                $storeId = (int)$store->getId();
                $cronEnabled = (bool)$this->getCronConfigData('enable', $storeId);

                if (!$cronEnabled) {
                    $this->logger->info(
                        "PAYU GATEWAY TXN STATUS CRON: disabled for website ({$website->getName()}), store ({$store->getName()})"
                    );

                    continue;
                }

                $this->logger->info(
                    "PAYU GATEWAY TXN STATUS CRON: Started for website ({$website->getName()}), store ({$store->getName()}), PID: $processId"
                );

                $orders = $this->getOrderCollection((string)$storeId);

                /** @var \Magento\Sales\Model\Order $order */
                foreach ($orders->getItems() as $order) {
                    /** @var \Magento\Sales\Model\Order\Payment $payment */
                    $payment = $order->getPayment();
                    $additionalInfo = $payment->getAdditionalInformation();
                    $transactionId = $payment->getLastTransId();
                    $code = $payment->getData('method');
                    $this->code = $code;

                    $id = $order->getIncrementId();
                    $this->logger->info("PAYU GATEWAY TXN STATUS CRON: ($processId) checking: $id");

                    if (!str_contains($code, 'payu_gateway')) {
                        $this->logger->info("PAYU GATEWAY TXN STATUS CRON: ($processId) not a PayU Gateway payment method");

                        continue;
                    }

                    if (isset($additionalInfo["fraud_details"])) {
                        if ($additionalInfo["fraud_details"]["return"]["transactionState"] === 'SUCCESSFUL') {
                            $this->logger->info("PAYU GATEWAY TXN STATUS CRON: ($id) ($processId) already successful");

                            continue;
                        }

                        $payUReference = $additionalInfo["fraud_details"]["return"]["payUReference"];
                    } else {
                        $payUReference = $additionalInfo["payUReference"] ?? $transactionId;
                    }

                    if (!isset($payUReference)) {
                        $this->logger->info("PAYU GATEWAY TXN STATUS CRON: ($processId) no PayU reference");

                        continue;
                    }

                    if (!$this->shouldDoCheck($order)) {
                        $this->logger->info("PAYU GATEWAY TXN STATUS CRON: ($id) ($processId) check delayed");

                        continue;
                    }

                    $this->logger->info("PAYU GATEWAY TXN STATUS CRON: ($id) ($processId) doing Check");

                    $order = $this->orderRepository->get($order->getId());

                    if ($order->getState() === Order::STATE_PROCESSING || $order->getStatus() === Order::STATE_PROCESSING) {
                        $this->logger->info(
                            "PAYU GATEWAY TXN STATUS CRON: order completed, skip processing. Order id = " . $order->getIncrementId()
                        );

                        continue;
                    }

                    if ($order->hasInvoices()) {
                        $this->logger->info(
                            "PAYU GATEWAY TXN STATUS CRON: ($processId) already invoiced, skip processing. order id = "
                            . $order->getIncrementId()
                        );

                        continue;
                    }

                    try {
                        $method = $payment->getMethodInstance();
                        $method->fetchTransactionInfo($payment, $payUReference);

                        $this->configurePayment($order, $payment);
                        $this->processReturn($order, $payment);
                    } catch (Exception $exception) {
                        $this->logger->info('PAYU GATEWAY TXN STATUS CRON: ' . $exception->getMessage());
                        continue;
                    }
                }
            }
        }

        $this->logger->info("PAYU GATEWAY TXN STATUS CRON: Ended, PID: $processId");
    }

    /**
     * @param OrderInterface|Order $order
     * @return bool
     */
    protected function shouldDoCheck(OrderInterface|Order $order): bool
    {
        $createdAt = strtotime($order->getCreatedAt());
        $updatedAt = strtotime($order->getUpdatedAt());

        $now = time();

        $minutesSinceCreated = (int) ceil(($now - $createdAt) / 60);
        $minutesSinceUpdated = $minutesSinceCreated - (int) ceil(($now - $updatedAt) / 60);

        $cronDelay = $this->getCronConfigData('cron_delay');

        if (empty($cronDelay)) {
            $cronDelay = "5";
        }

        $this->logger->info(
            "PAYU GATEWAY TXN STATUS CRON: ($this->processId) Minutes since created: $minutesSinceCreated - Delay: $cronDelay mins"
        );
        $this->logger->info(
            "PAYU GATEWAY TXN STATUS CRON: ($this->processId) Minutes since updated: $minutesSinceUpdated - Delay: $cronDelay mins"
        );

        $minutesSinceCreated = $minutesSinceCreated - $cronDelay;
        $minutesSinceUpdated = $minutesSinceUpdated - $cronDelay;

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
            if (
                (($v[0] <= $minutesSinceCreated) && ($minutesSinceCreated <= $v[1])) &&
                (!(($v[0] <= $minutesSinceUpdated) && ($minutesSinceUpdated <= $v[1])))
            ) {
                return true;
            }
        }

        if (((744 <= $minutesSinceCreated)) && (!((744 <= $minutesSinceUpdated)))) {
            return true;
        }

        $this->logger->info("PAYU GATEWAY TXN STATUS CRON: ($this->processId) Check Not Needed");

        return false;
    }

    /**
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     */
    public function getCronConfigData(string $field, int|null $storeId = null): mixed
    {
        $path = sprintf(self::CRON_CONFIG_PATTERN, $field);

        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param OrderInterface|Order $order
     * @param Payment $payment
     * @return void
     */
    protected function configurePayment(OrderInterface|Order $order, InfoInterface $payment): void
    {
        $payment->unsTransactionId();
        $payment->setCheckTransactionStatus(true);
        $payment->setParentTransactionId($payment->getLastTransId());
        $order->setPayment($payment);
    }
}
