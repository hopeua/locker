<?php
namespace Hope\Locker;

/**
 * Lock some instance
 */
interface LockerInterface
{
    /**
     * Lock instance
     *
     * @return null
     */
    public function lock();

    /**
     * Unlock instance
     *
     * @return null
     */
    public function unlock();

    /**
     * Checks if instance locked
     *
     * @return bool
     */
    public function isLocked();
}