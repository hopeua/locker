<?php
namespace Tests\Locker;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Hope\Locker\FileLocker;

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
     * @covers \Hope\Locker\FileLocker
     *
     */
    public function testBasic()
    {
        $lockId   = 'test.one';
        $lockFile = $this->getLockFileName($lockId);

        // Init Locker
        $locker = new FileLocker($lockId, 'vfs://lock');
        $this->assertInstanceOf('Hope\Locker\FileLocker', $locker);

        // Lock
        $locker->lock();
        $this->assertTrue($this->root->hasChild($lockFile));

        /**
         * @var \org\bovigo\vfs\vfsStreamFile $vfsLockFile
         */
        $vfsLockFile = $this->root->getChild($lockFile);
        $this->assertRegExp('~\d+~', $vfsLockFile->getContent());

        // Check is locked
        $this->assertTrue($locker->isLocked());

        // Release lock
        $locker->release();
        $this->assertFalse($this->root->hasChild($lockFile));

        // Check is unlocked
        $this->assertFalse($locker->isLocked());
    }

    public function testUnlockIfPidNotExists()
    {
        $lockId   = 'test.pid';
        $lockFile = $this->getLockFileName($lockId);

        $locker = new FileLocker($lockId, 'vfs://lock');
        $locker->lock();

        /**
         * @var \org\bovigo\vfs\vfsStreamFile $vfsLockFile
         */
        $vfsLockFile = $this->root->getChild($lockFile);
        $vfsLockFile->setContent('99999');

        $this->assertFalse($locker->isLocked());
    }

    /**
     * @expectedException \Hope\Locker\LockerException
     * @expectedExceptionCode \Hope\Locker\LockerException::INVALID_LOCK_DIR
     */
    public function testExceptionInvalidDir()
    {
        $lockId = 'test.ex.dir';
        new FileLocker($lockId, 'vfs://notExists');
    }

    /**
     * @expectedException \Hope\Locker\LockerException
     * @expectedExceptionCode \Hope\Locker\LockerException::LOCKED
     */
    public function testExceptionLocked()
    {
        $lockId = 'test.ex.locked';
        $locker = new FileLocker($lockId, 'vfs://lock');
        $locker->lock();
        $locker->lock();
    }

    /**
     * @expectedException \Hope\Locker\LockerException
     * @expectedExceptionCode \Hope\Locker\LockerException::FS_READ
     */
    public function testExceptionRead()
    {
        $lockId   = 'test.ex.read';
        $lockFile = $this->getLockFileName($lockId);

        $locker = new FileLocker($lockId, 'vfs://lock');
        $locker->lock();

        /**
         * @var \org\bovigo\vfs\vfsStreamFile $vfsLockFile
         */
        $vfsLockFile = $this->root->getChild($lockFile);
        $vfsLockFile->chmod(0400)
                    ->chown(1);

        $locker->isLocked();
    }

    /**
     * @expectedException \Hope\Locker\LockerException
     * @expectedExceptionCode \Hope\Locker\LockerException::FS_WRITE
     */
    public function testExceptionWrite()
    {
        $lockId = 'test.ex.write';
        $locker = new FileLocker($lockId, 'vfs://lock');

        $this->root
            ->chmod(0400)
            ->chown(1);

        $locker->lock();
    }

    /**
     * @expectedException \Hope\Locker\LockerException
     * @expectedExceptionCode \Hope\Locker\LockerException::FS_DEL
     */
    public function testExceptionDel()
    {
        $lockId   = 'test.ex.del';

        $locker = new FileLocker($lockId, 'vfs://lock');
        $locker->lock();

        // Prevent lockfile from removing
        $this->root
             ->chmod(0400)
             ->chown(1);

        $locker->release();
    }

    /**
     * @expectedException \Hope\Locker\LockerException
     * @expectedExceptionCode \Hope\Locker\LockerException::LOCK_CONTENT
     */
    public function testExceptionLockFileContent()
    {
        $lockId   = 'test.ex.content';
        $lockFile = $this->getLockFileName($lockId);

        $locker = new FileLocker($lockId, 'vfs://lock');
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