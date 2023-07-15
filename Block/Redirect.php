<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Block;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Info;
use PayU\Gateway\Helper\Data;
use PayU\Gateway\Helper\DataFactory;

/**
 * class Redirect
 * @package PayU\Gateway\Block
 */
class Redirect extends Info
{
    /**
     * @var DataFactory
     */
    protected DataFactory $dataFactory;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * Constructor
     *
     * @param Context $context
     * @param DataFactory $dataFactory
     * @param ManagerInterface $messageManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        DataFactory $dataFactory,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        $this->dataFactory = $dataFactory;
        $this->messageManager = $messageManager;

        parent::__construct($context, $data);
    }

    /**
     * Get helper data
     *
     * @param string $area
     * @return Data
     *@throws LocalizedException
     */
    public function getHelper(string $area): Data
    {
        return $this->dataFactory->create($area);
    }

    /**
     * {inheritdoc}
     */
    protected function _beforeToHtml(): Redirect
    {
        $this->addSuccessMessage();

        return parent::_beforeToHtml();
    }

    /**
     * Add success message
     *
     * @return void
     */
    private function addSuccessMessage(): void
    {
        $params = $this->getParams();

        if (isset($params['PayUReference'])) {
            $this->messageManager->addSuccessMessage(__('Redirecting to payment gateway...Please wait'));
        }
    }
}
