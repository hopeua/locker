<?php
namespace Hope\Tests\Locker;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Hope\Locker\FileLocker;
use Hope\Locker\LockerException;

class FileLockerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Root directory of virtual FS
     *
     * @var vfsStreamDirectory
     */
    protected $root;

    public function setUp()
    {
        // Init virtual FS
        $this->root = vfsStream::setup('lock');
    }

    /**
     * Basic workflow test
     *
     * @covers Worker\Lib\Locker\FileLocker
     *
     */
    public function testBasic()
    {
        $lockId   = 'testOne';
        $lockFile = $this->getLockFileName($lockId);

        // Init Locker
        $locker = new FileLocker($lockId, ['lockDir' => 'vfs://lock']);
        $this->assertInstanceOf('Hope\Locker\FileLocker', $locker);

        // Lock
        $locker->lock();
        $this->assertTrue($this->root->hasChild($lockFile));
        $this->assertRegExp('~\d+~', $this->root->getChild($lockFile)->getContent());

        // Check is locked
        $this->assertTrue($locker->isLocked());

        // Unlock
        $locker->unlock();
        $this->assertFalse($this->root->hasChild($lockFile));

        // Check is unlocked
        $this->assertFalse($locker->isLocked());
    }

    public function testExceptionId()
    {
        $this->setExpectedException('Hope\Locker\LockerException', '', LockerException::INVALID_ID);

        $lockId = 'wrongId.%';
        $locker = new FileLocker($lockId);
    }

    public function testExceptionLocked()
    {
        $this->setExpectedException('Hope\Locker\LockerException', '', LockerException::LOCKED);

        $lockId = 'testExLocked';
        $locker = new FileLocker($lockId, ['lockDir' => 'vfs://lock']);
        $locker->lock();
        $locker->lock();
    }

    public function testExceptionRead()
    {
        $this->setExpectedException('Hope\Locker\LockerException', '', LockerException::FS_READ);

        $lockId   = 'testExRead';
        $lockFile = $this->getLockFileName($lockId);

        $locker = new FileLocker($lockId, ['lockDir' => 'vfs://lock']);
        $locker->lock();

        /**
         * @var \org\bovigo\vfs\vfsStreamFile $vfsLockFile
         */
        $vfsLockFile = $this->root->getChild($lockFile);
        $vfsLockFile->chmod(0400)
                    ->chown(1);

        $locker->isLocked();
    }

    public function testExceptionWrite()
    {
        $this->setExpectedException('Hope\Locker\LockerException', '', LockerException::FS_WRITE);

        $lockId = 'testExWrite';
        $locker = new FileLocker($lockId, ['lockDir' => 'vfs://notExists']);
        $locker->lock();
    }

    public function testExceptionDel()
    {
        $this->setExpectedException('Hope\Locker\LockerException', '', LockerException::FS_DEL);

        $lockId   = 'testExDel';

        $locker = new FileLocker($lockId, ['lockDir' => 'vfs://lock']);
        $locker->lock();

        // Prevet lockfile form removing
        $this->root
             ->chmod(0400)
             ->chown(1);

        $locker->unlock();
    }

    public function testExceptionLockFileContent()
    {
        $this->setExpectedException('Hope\Locker\LockerException', '', LockerException::LOCK_CONTENT);

        $lockId   = 'testExContent';
        $lockFile = $this->getLockFileName($lockId);

        $locker = new FileLocker($lockId, ['lockDir' => 'vfs://lock']);
        $locker->lock();

        /**
         * @var \org\bovigo\vfs\vfsStreamFile $vfsLockFile
         */
        $vfsLockFile = $this->root->getChild($lockFile);
        $vfsLockFile->setContent('NotAPid');

        $locker->isLocked();
    }

    /**
     * Get the lock file name by ID
     *
     * @param $id
     *
     * @return string
     */
    private function getLockFileName($id)
    {
        return 'lock/' . $id . '.lock';
    }
}