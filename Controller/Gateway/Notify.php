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
use PayU\Model\XmlHelper;

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

        if (empty($sxe)) {
            http_response_code(500);
        }

        $ipnData = XmlHelper::parseXMLToArray($sxe);
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON)->setJsonData('{}');

        if (!$ipnData) {
            http_response_code(500);

            return $resultJson;
        }

        $incrementId = $ipnData['MerchantReference'];
        $order = $incrementId ? $this->orderFactory->create()->loadByIncrementId($incrementId) : false;

        $this->responseProcessor->notify($order, $ipnData);
        http_response_code(200);

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
