<?php
namespace Hope\Locker;

/**
 * Base class for Locker implementation
 */
abstract class CommonLocker implements LockerInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function lock();
    /**
     * {@inheritdoc}
     */
    abstract public function unlock();
    /**
     * {@inheritdoc}
     */
    abstract public function isLocked();
}
