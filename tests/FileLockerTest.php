<?php
namespace Loevgaard\Tests\Locker;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Loevgaard\Locker\FileLocker;

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
     * @covers \Loevgaard\Locker\FileLocker
     *
     */
    public function testBasic()
    {
        $lockId   = 'testOne';
        $lockFile = $this->getLockFileName($lockId);

        // Init Locker
        $locker = new FileLocker($lockId, 'vfs://lock');
        $this->assertInstanceOf('Loevgaard\Locker\FileLocker', $locker);

        // Lock
        $locker->lock();
        $this->assertTrue($this->root->hasChild($lockFile));

        /**
         * @var \org\bovigo\vfs\vfsStreamFile $vfsLockFile
         */
        $vfsLockFile = $this->root->getChild($lockFile);
        $this->assertRegExp('~\d+~', $vfsLockFile->getContent());

        // Unlock
        $locker->release();
        $this->assertFalse($this->root->hasChild($lockFile));
    }

    public function testUnlockIfPidNotExists()
    {
        $lockId   = 'testPid';
        $lockFile = $this->getLockFileName($lockId);

        $locker = new FileLocker($lockId, 'vfs://lock');
        $locker->lock();

        /**
         * @var \org\bovigo\vfs\vfsStreamFile $vfsLockFile
         */
        $vfsLockFile = $this->root->getChild($lockFile);
        $vfsLockFile->setContent('99999');
    }

    /**
     * @expectedException \Loevgaard\Locker\LockerException
     */
    public function testExceptionId()
    {
        $lockId = 'wrongId.%';
        new FileLocker($lockId);
    }

    /**
     * @expectedException \Loevgaard\Locker\LockerException
     */
    public function testExceptionLocked()
    {
        $lockId = 'testExLocked';
        $locker = new FileLocker($lockId, 'vfs://lock');
        $locker->lock();
        $locker->lock();
    }

    /**
     * @expectedException \Loevgaard\Locker\LockerException
     */
    public function testExceptionRead()
    {
        $lockId   = 'testExRead';
        $lockFile = $this->getLockFileName($lockId);

        $locker = new FileLocker($lockId, 'vfs://lock');
        $locker->lock();

        /**
         * @var \org\bovigo\vfs\vfsStreamFile $vfsLockFile
         */
        $vfsLockFile = $this->root->getChild($lockFile);
        $vfsLockFile->chmod(0400)
                    ->chown(1);
    }

    /**
     * @expectedException \Loevgaard\Locker\LockerException
     */
    public function testExceptionWrite()
    {
        $lockId = 'testExWrite';
        $locker = new FileLocker($lockId, 'vfs://notExists');
        $locker->lock();
    }

    /**
     * @expectedException \Loevgaard\Locker\LockerException
     */
    public function testExceptionDel()
    {
        $lockId   = 'testExDel';

        $locker = new FileLocker($lockId, 'vfs://lock');
        $locker->lock();

        // Prevet lockfile form removing
        $this->root
             ->chmod(0400)
             ->chown(1);

        $locker->release();
    }

    /**
     * @expectedException \Loevgaard\Locker\LockerException
     */
    public function testExceptionLockFileContent()
    {
        $lockId   = 'testExContent';
        $lockFile = $this->getLockFileName($lockId);

        $locker = new FileLocker($lockId, 'vfs://lock');
        $locker->lock();

        /**
         * @var \org\bovigo\vfs\vfsStreamFile $vfsLockFile
         */
        $vfsLockFile = $this->root->getChild($lockFile);
        $vfsLockFile->setContent('NotAPid');
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