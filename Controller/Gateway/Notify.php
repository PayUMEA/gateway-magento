<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Controller\Gateway;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use PayU\Gateway\Controller\AbstractAction;
use PayU\Framework\XmlHelper;

/**
 * class Notify
 * @package PayU\Gateway\Controller\Gateway
 */
class Notify extends AbstractAction implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * Process Instant Payment Notification (IPN) from PayU
     * @throws LocalizedException
     */
    public function execute(): Json
    {
        $processId = uniqid();
        $processString = self::class;

        $this->getSession()->setPayUProcessId($processId);
        $this->getSession()->setPayUProcessString($processString);

        $this->logger->info("($processId) START $processString");

        $postData = file_get_contents("php://input");
        $sxe = simplexml_load_string($postData);

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON)->setJsonData('{}');

        if (empty($sxe)) {
            $this->respond('500', 'Instant Payment Notification data is empty');

            return $resultJson;
        }

        $ipnData = XmlHelper::parseXMLToArray($sxe);

        if (!$ipnData) {
            $this->respond('500', 'Failed to decode Instant Payment Notification data.');

            return $resultJson;
        }

        $this->respond();

        $incrementId = $ipnData['MerchantReference'];
        $order = $incrementId ? $this->orderFactory->create()->loadByIncrementId($incrementId) : false;

        if ($order) {
            $this->responseProcessor->notify($order, $ipnData);
        }

        return $resultJson;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
}
