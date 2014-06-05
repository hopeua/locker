<?php
namespace Hope\Locker;

/**
 * Locker aware interface
 */
interface LockerAwareInterface
{
    /**
     * Sets the Locker.
     *
     * @param LockerInterface|null $locker A LockerInterface instance or null
     *
     */
    public function setLocker(LockerInterface $locker = null);
}