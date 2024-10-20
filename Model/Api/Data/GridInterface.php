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
    const ENTITY_ID = 'entity_id';
    const INCREMENT_ID = 'increment_id';
    const LOCK = 'lock';
    const STATUS = 'status';
    const PROCESS_ID = 'process_id';

    const PROCESS_CLASS = 'process_class';
    const CREATED_AT = 'created_at';
    /**
     * Get EntityId.
     *
     * @return int
     */
    public function getEntityId();
    /**
     * Set EntityId.
     */
    public function setEntityId($entityId);
    /**
     * Get Increment Id.
     *
     * @return string
     */
    public function getIncrementId(): string;
    /**
     * Set Increment Id.
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
     */
    public function setCreatedAt(string $createdAt);
}
