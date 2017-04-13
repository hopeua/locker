# Process Locker for PHP scripts

Largely inspired by [HopeUA/Locker](https://github.com/HopeUA/locker)

## Usage
    $locker = new FileLocker('large-process', ['lockDir' => '/tmp/lock']);
    if ($locker->isLocked()) {
        die('Already locked');
    }

    $locker->lock();
    ... some code ...
    $locker->unlock();
