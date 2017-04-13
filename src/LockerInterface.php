<?php
namespace Hope\Locker;

/**
 * Interface for different lockers
 */
interface LockerInterface
{
    /**
     * Locks the locker
     *
     * @return void
     * @throws LockerException
     */
    public function lock();

    /**
     * Releases the lock
     *
     * @return void
     * @throws LockerException
     */
    public function release();

    /**
     * Check if lock is locked
     *
     * @return bool
     * @throws LockerException
     */
    public function isLocked();
}
