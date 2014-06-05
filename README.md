Process Locker for scripts
==========================
Usage
-----
    $locker = new FileLocker('large-process', ['lockDir' => '/tmp/lock']);
    if ($locker->isLocked()) {
        die('Already locked');
    }
    
    $locker->lock();
    ... some code ...
    $locker->unlock();