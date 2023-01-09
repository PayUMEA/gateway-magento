<?php declare(strict_types=1);
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace PayU\Gateway\Cron;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PayU\EasyPlus\Model\AbstractPayU;
use PayU\Gateway\Model\Adapter\PayUAdapter;
use PayU\Gateway\Model\Adapter\PayUAdapterFactory;
use Psr\Log\LoggerInterface;

/**
 * class CheckTransactionState
 * @package PayU\Gateway\Cron
 */
class CheckTransactionState
{
    private const CONFIG_PATTERN = 'payment/%s/%s';
    private const CRON_CONFIG_PATTERN = 'payment/payu_gateway_cron/%';

    /**
     * @var string
     */
    protected string $processId = '';

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var PayUAdapter
     */
    protected payUAdapter $payUAdapter;

    /**
     * @var EncryptorInterface
     */
    protected EncryptorInterface $_encryptor;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $_scopeConfig;

    /**
     * @var OrderFactory
     */
    protected OrderFactory $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var Registry|null
     */
    protected ?Registry $coreRegistry = null;

    /**
     * @var string|null
     */
    protected ?string $_code = null;

    /**
     * @var string|null
     */
    protected ?string $_payUReference = null;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $_orderCollectionFactory;

    /**
     * @var OrderSender
     */
    private OrderSender $orderSender;

    /**
     * @var InvoiceSender
     */
    private InvoiceSender $invoiceSender;

    /**
     * @var InvoiceService
     */
    private InvoiceService $_invoiceService;

    /**
     * @var Transaction
     */
    private Transaction $_transaction;

    /**
     * @var Config
     */
    private Config $OrderConfig;

    /**
     * CheckTransactionState constructor.
     * @param LoggerInterface $logger
     * @param PayUAdapterFactory $apiFactory
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     * @param OrderFactory $orderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param State $state
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param Config $OrderConfig
     */
    public function __construct(
        LoggerInterface $logger,
        PayUAdapterFactory $apiFactory,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        Registry $registry,
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfig,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        State $state,
        CollectionFactory $orderCollectionFactory,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        InvoiceService $invoiceService,
        Transaction $transaction,
        Config $OrderConfig
    ) {
        $this->logger = $logger;
        $this->payUAdapter = $apiFactory->create();
        $this->_encryptor = $encryptor;
        $this->_storeManager = $storeManager;
        $this->coreRegistry = $registry;
        $this->_scopeConfig = $scopeConfig;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->OrderConfig = $OrderConfig;
    }

    /**
     * @param $data
     * @param Order $order
     * @return void
     * @throws LocalizedException
     */
    public function processReturn($data, Order $order): void
    {
        $data = (array)$data;
        $data['basket'] = [$data['basket']];

        $transactionNotes = "<strong>-----PAYU STATUS CHECKED ---</strong><br />";

        if (!isset($data['resultCode']) || (in_array($data['resultCode'], ['POO5', 'EFTPRO_003', '999', '305']))) {
            $this->logger->info("($this->processId) No resultCode");
            $this->logger->info(json_encode($data));

            return;
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
            $this->logger->info("($this->processId) No transactionState");
            $this->logger->info(json_encode($data));

            return;
        }

        $transactionNotes .= "PayU Reference: " . $data["payUReference"] . "<br />";
        $transactionNotes .= "PayU Payment Status: " . $data["transactionState"] . "<br /><br />";

        switch ($data['transactionState']) {
            case 'SUCCESSFUL':
                $order->addCommentToStatusHistory($transactionNotes);
                $this->invoiceAndNotifyCustomer($order);
                break;
            case 'FAILED':
            case 'TIMEOUT':
            case 'EXPIRED':
                $order->registerCancellation($transactionNotes);
                $this->orderRepository->save($order);
                break;
            default:
                $order->addCommentToStatusHistory($transactionNotes, true);
                break;
        }
    }

    /**
     * @return Collection
     */
    public function getOrderCollection(): Collection
    {
        return $this->_orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter(
                'status',
                ['in' => ['pending_payment']]
            );
    }

    /**
     * @return void
     * @throws LocalizedException|Exception
     */
    public function execute(): void
    {
        $bypass_payu_cron = $this->getCRONConfigData('bypass_payu_cron');

        if ('1' ===  $bypass_payu_cron) {
            $this->logger->info("PayU CRON DISABLED");

            return;
        }

        $processId = uniqid();
        $this->processId = $processId;

        $this->logger->info("PayU CRON Started, PID: $processId");

        $orders = $this->getOrderCollection();

        foreach ($orders->getItems() as $order) {
            $payment = $order->getPayment();
            $additionalInfo = $payment->getAdditionalInformation();
            $code = $payment->getData('method');

            $id = $order->getEntityId();
            $this->logger->info("($processId) Checking: $id");

            if (!str_contains($code, 'payumea')) {
                $this->logger->info("($processId) Not PayU");

                continue;
            }

            if (isset($additionalInfo["fraud_details"])) {
                if ($additionalInfo["fraud_details"]["return"]["transactionState"] === 'SUCCESSFUL') {
                    $this->logger->info("($processId) ($id) Already Success");

                    continue;
                }

                $payUReference = $additionalInfo["fraud_details"]["return"]["payUReference"];
            } else {
                if (!isset($additionalInfo["payUReference"])) {
                    $this->logger->info("($processId) No Details");

                    continue;
                }

                $payUReference = $additionalInfo["payUReference"];
            }

            $state_test = $additional_info["fraud_details"]["return"]["transactionState"] ?? '';

            switch ($state_test) {
                case AbstractPayU::TRANS_STATE_SUCCESSFUL:
                case AbstractPayU::TRANS_STATE_FAILED:
                case AbstractPayU::TRANS_STATE_EXPIRED:
                case AbstractPayU::TRANS_STATE_TIMEOUT:
                    $this->logger->info(" ($id) Already Success Status");
                    break;
                default:
                    if (!$this->shouldDoCheck($order)) {
                        $this->logger->info("($processId) ($id) Check not timed");

                        break;
                    }

                    $this->logger->info("($processId) ($id) Doing Check");
                    // We will check trans state again
                    $this->_code = $code;
                    $this->_payUReference = $payUReference;

                    $result = $this->payUAdapter->search($this->_payUReference);

                    $return = $result->getReturn();

                    $order = $this->orderRepository->get($order->getId());

                    if ($order->getState() == \Magento\Sales\Model\Order::STATE_PROCESSING) {
                        $this->logger->info("Order Completed, no need to run... order id = " . $order->getId());
                        break;
                    }

                    if ($order->hasInvoices()) {
                        $this->logger->info("($processId) Already Invoiced, no need to run... order id = " . $order->getId());
                        break;
                    }

                    try {
                        $this->processReturn($return, $order);
                    } catch (Exception $exception) {
                        $this->logger->info($exception->getMessage());
                        $this->logger->info(json_encode($return));
                    }

                    $order->setUpdatedAt(null);
                    $this->orderRepository->save($order);
                    break;
            }
        }

        // Do your Stuff
        $this->logger->info("PayU CRON Ended, PID: $processId");
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

        $cronDelay = $this->getCRONConfigData('payu_gateway_cron_delay');

        if (empty($cronDelay)) {
            $cronDelay = "5";
        }

        $this->logger->info("($this->processId) minutes_created: $minutesCreated - Delay: $cronDelay");
        $this->logger->info("($this->processId) minutes_updated: $minutesUpdated - Delay: $cronDelay");

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

        $this->logger->info("($this->processId) Check Not Needed");

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
            return $this->_encryptor->decrypt($this->getConfigData($key, $storeId));
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
        $path = sprintf(self::CONFIG_PATTERN, $this->_code, $field);

        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param string $field
     * @param $storeId
     * @return mixed
     */
    public function getCRONConfigData(string $field, $storeId = null): mixed
    {
        $path = sprintf(self::CRON_CONFIG_PATTERN, $field);

        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param Order $order
     * @return void
     * @throws LocalizedException
     */
    protected function invoiceAndNotifyCustomer(Order $order): void
    {
        $id = $order->getIncrementId();

        try {
            $order->setCanSendNewEmailFlag(true);
            $this->orderSender->send($order);

            $this->logger->info(
                "($this->processId) ($id) PayU CRON: can_invoice (initial check): " . $order->canInvoice()
            );

            if ($order->canInvoice()) {

                /**
                 * 2021/06/16 Double Invoice Correction
                 * Force reload order state to check status just before update,
                 * discard invoice if status changed since start of process
                 */
                $orderStatusTest = $this->orderFactory->create()->loadByIncrementId($order->getIncrementId());
                $this->logger->info(
                    '($this->process_id) ($id) PayU CRON: can_invoice (double check): ' . $orderStatusTest->canInvoice()
                );

                if (!$orderStatusTest->canInvoice()) {
                    // Simply just skip this section
                    goto cannot_invoice_marker;
                }

                $status = $this->OrderConfig->getStateDefaultStatus('processing');
                $order->setState("processing")->setStatus($status);
                $this->orderRepository->save($order);

                $invoice = $this->_invoiceService->prepareInvoice($order);

                $invoice->register();
                $invoice->save();
                $transactionService = $this->_transaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );
                $transactionService->save();
                $this->logger->info(" ($this->processId) ($id) PayU CRON: INVOICED");
                $this->invoiceSender->send($invoice);
                $order->addCommentToStatusHistory(
                    __('Notified customer about invoice #%1.', $invoice->getId())
                )->setIsCustomerNotified(true);
                $this->orderRepository->save($order);
            } else {
                /**
                 * Double Invoice Correction
                 * 2021/06/16
                 */
                cannot_invoice_marker:
                $this->logger->info('($this->process_id)  ($id) Already invoiced, skip');
            }
        } catch (Exception $e) {
            throw new LocalizedException(new Phrase("Error encountered while capturing your order"));
        }
    }
}
