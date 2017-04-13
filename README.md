# Process Locker for PHP scripts

[![Test passing](https://travis-ci.org/HopeUA/locker.svg?branch=master)](https://travis-ci.org/HopeUA/locker)
[![Latest Stable Version](https://poser.pugx.org/hope/locker/v/stable.svg)](https://packagist.org/packages/hope/locker)

## Usage
    $locker = new FileLocker('large-process', ['lockDir' => '/tmp/lock']);
    if ($locker->isLocked()) {
        die('Already locked');
    }

    $locker->lock();
    ... some code ...
    $locker->unlock();
