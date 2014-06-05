<?php
namespace Hope\Locker;

class LockerException extends \RuntimeException
{
    const INVALID_ID   = 10;
    const LOCKED       = 20;
    const LOCK_CONTENT = 30;
    const FS_READ      = 50;
    const FS_WRITE     = 51;
    const FS_DEL       = 52;
}