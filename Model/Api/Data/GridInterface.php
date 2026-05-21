<?php
/**
 * Copyright © 2024 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Model\Api\Data;

interface GridInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    public const string ENTITY_ID = 'entity_id';
    public const string INCREMENT_ID = 'increment_id';
    public const string LOCK = 'lock';
    public const string STATUS = 'status';
    public const string PROCESS_ID = 'process_id';
    public const string PROCESS_CLASS = 'process_class';
    public const string CREATED_AT = 'created_at';

    /**
     * Get EntityId.
     *
     * @return int
     */
    public function getEntityId();
    /**
     * Set EntityId.
     *
     * @param int $entityId
     */
    public function setEntityId(int $entityId);
    /**
     * Get Increment Id.
     *
     * @return string
     */
    public function getIncrementId(): string;
    /**
     * Set Increment Id.
     *
     * @param string $incrementId
     */
    public function setIncrementId(string $incrementId);
    /**
     * Get Lock.
     *
     * @return bool
     */
    public function getLock(): bool;
    /**
     * Set Lock.
     *
     * @param bool $lock
     */
    public function setLock(bool $lock);
    /**
     * Get Status.
     *
     * @return string
     */
    public function getStatus(): string;
    /**
     * Set Status.
     *
     * @param string $status
     */
    public function setStatus(string $status);
    /**
     * Get Process Id.
     *
     * @return string
     */
    public function getProcessId(): string;
    /**
     * Set Process Id.
     *
     * @param string $processId
     */
    public function setProcessId(string $processId);
    /**
     * Get Process Class.
     *
     * @return string
     */
    public function getProcessClass(): string;
    /**
     * Set Process Class.
     *
     * @param string $processClass
     */
    public function setProcessClass(string $processClass);
    /**
     * Get CreatedAt.
     *
     * @return string
     */
    public function getCreatedAt(): string;
    /**
     * Set CreatedAt.
     *
     * @param string $createdAt
     */
    public function setCreatedAt(string $createdAt);
}
