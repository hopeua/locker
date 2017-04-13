<?php
namespace Tests\Locker;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\visitor\vfsStreamPrintVisitor;
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
        $lockId   = 'testOne';
        $lockFile = $this->getLockFileName($lockId);

        // Init Locker
        $locker = new FileLocker($lockId, ['lockDir' => 'vfs://lock']);
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

        // Unlock
        $locker->unlock();
        $this->assertFalse($this->root->hasChild($lockFile));

        // Check is unlocked
        $this->assertFalse($locker->isLocked());
    }

    public function testUnlockIfPidNotExists()
    {
        $lockId   = 'testPid';
        $lockFile = $this->getLockFileName($lockId);

        $locker = new FileLocker($lockId, ['lockDir' => 'vfs://lock']);
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
     */
    public function testExceptionId()
    {
        $lockId = 'wrongId.%';
        $locker = new FileLocker($lockId);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     */
    public function testMissingOptions()
    {
        $lockId = 'testNoOption';
        $locker = new FileLocker($lockId, []);
    }

    /**
     * @expectedException \Hope\Locker\LockerException
     */
    public function testExceptionLocked()
    {
        $lockId = 'testExLocked';
        $locker = new FileLocker($lockId, ['lockDir' => 'vfs://lock']);
        $locker->lock();
        $locker->lock();
    }

    /**
     * @expectedException \Hope\Locker\LockerException
     */
    public function testExceptionRead()
    {
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

    /**
     * @expectedException \Hope\Locker\LockerException
     */
    public function testExceptionWrite()
    {
        $lockId = 'testExWrite';
        $locker = new FileLocker($lockId, ['lockDir' => 'vfs://notExists']);
        $locker->lock();
    }

    /**
     * @expectedException \Hope\Locker\LockerException
     */
    public function testExceptionDel()
    {
        $lockId   = 'testExDel';

        $locker = new FileLocker($lockId, ['lockDir' => 'vfs://lock']);
        $locker->lock();

        // Prevet lockfile form removing
        $this->root
             ->chmod(0400)
             ->chown(1);

        $locker->unlock();
    }

    /**
     * @expectedException \Hope\Locker\LockerException
     */
    public function testExceptionLockFileContent()
    {
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