Process Locker for scripts
==========================
![Test passing](https://travis-ci.org/HopeUA/locker.svg?branch=master)
Usage
-----
    $locker = new FileLocker('large-process', ['lockDir' => '/tmp/lock']);
    if ($locker->isLocked()) {
        die('Already locked');
    }

    $locker->lock();
    ... some code ...
    $locker->unlock();
