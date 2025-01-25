<?php
/**
 * Copyright © 2024 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace PayU\Gateway\Model\Lock;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use PayU\Gateway\Model\Api\Data\GridInterface;
use PayU\Gateway\Model\ResourceModel;
/**
 */
class Transaction extends AbstractModel implements IdentityInterface, GridInterface
{
    /**
     * Name of object id field
     *
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * BlogManager Blog cache tag.
     */
    const CACHE_TAG = 'payu_payu_gateway_transaction';

    /**
     * @var string
     */
    protected $_cacheTag = 'payu_payu_gateway_transaction';
    /**
     * @var string
     */
    protected $_eventPrefix = 'payu_payu_gateway_transaction';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Transaction::class);
    }

    /**
     * Get Identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get Increment Id
     *
     * @return string
     */
    public function getIncrementId(): string
    {
        return parent::getData(GridInterface::INCREMENT_ID);
    }

    /**
     * Set Increment Id
     *
     * @param string $incrementId
     * @return Transaction
     */
    public function setIncrementId(string $incrementId)
    {
        return $this->setData(GridInterface::INCREMENT_ID, $incrementId);
    }

    /**
     * Get Lock
     *
     * @return bool
     */
    public function getLock(): bool
    {
        return parent::getData(GridInterface::LOCK);
    }

    /**
     * Set Lock
     *
     * @param bool $lock
     * @return Transaction
     */
    public function setLock(bool $lock)
    {
        return $this->setData(GridInterface::LOCK, $lock);
    }

    /**
     * Get Status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return parent::getData(GridInterface::STATUS);
    }

    /**
     * Set Status
     *
     * @param string $status
     * @return Transaction
     */
    public function setStatus(string $status)
    {
        return $this->setData(GridInterface::STATUS, $status);
    }

    /**
     * Get Process Id
     *
     * @return string
     */
    public function getProcessId(): string
    {
        return parent::getData(GridInterface::PROCESS_ID);
    }

    /**
     * Set Process Id
     *
     * @param string $processId
     * @return Transaction
     */
    public function setProcessId(string $processId)
    {
        return $this->setData(GridInterface::PROCESS_ID, $processId);
    }

    /**
     * Get Process Class
     *
     * @return string
     */
    public function getProcessClass(): string
    {
        return parent::getData(GridInterface::PROCESS_CLASS);
    }

    /**
     * Set Process Class
     *
     * @param string $processClass
     * @return Transaction
     */
    public function setProcessClass(string $processClass)
    {
        return $this->setData(GridInterface::PROCESS_CLASS, $processClass);
    }

    /**
     * Get CreatedAt.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->getData(GridInterface::CREATED_AT);
    }
    /**
     * Set CreatedAt.
     */
    public function setCreatedAt(string $createdAt)
    {
        return $this->setData(GridInterface::CREATED_AT, $createdAt);
    }
}
