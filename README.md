Process Locker for scripts
==========================
Usage
-----
    $locker = new FileLocker($lockId, ['lockDir' => 'vfs://lock']);
    if ($locker->isLocked()) {
        die('Already locked');
    }
    $locker->lock();
    ... some code ...
    $locker->unlock();