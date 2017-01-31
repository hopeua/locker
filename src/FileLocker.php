<?php
namespace Loevgaard\Locker;

/**
 * Lock using file and id of process
 */
class FileLocker implements LockerInterface
{
    /**
     * @var string ID of the lock
     */
    private $id;

    /**
     * @var string RegExp for Lock ID
     */
    private $regId = '~^[a-zA-Z0-9\-_]+$~';

    /**
     * @var string RegExp for Process ID
     */
    private $regPid = '~^\d+$~';

    /**
     * The lock directory
     *
     * @var string
     */
    private $lockDir;

    public function __construct($id, $lockDir = null)
    {
        // Test ID
        if (!preg_match($this->regId, $id)) {
            throw new LockerException('Invalid ID', LockerException::INVALID_ID);
        }

        if($lockDir) {
            if(!is_dir($lockDir) || !is_writable($lockDir)) {
                throw new LockerException('Invalid lock dir', LockerException::INVALID_LOCK_DIR);
            }
        } else {
            $lockDir = sys_get_temp_dir();
        }

        $this->id      = $id;
        $this->lockDir = $lockDir;
    }

    /**
     * {@inheritdoc}
     */
    public function lock()
    {
        // Check if lock file exists
        if (file_exists($this->getFilePath())) {
            // Get pid of last process
            $pid = @file_get_contents($this->getFilePath());
            if (false === $pid) {
                throw new LockerException(sprintf('Failed to read the lock file %s', $this->getFilePath()), LockerException::FS_READ);
            }

            // Check if pid is valid
            if (!preg_match($this->regPid, $pid)) {
                throw new LockerException(sprintf('Unexpected content in lock file %s', $this->getFilePath()), LockerException::LOCK_CONTENT);
            }

            // Check if pid exist
            // does not work on windows so we exclude this check on windows
            if (stripos(PHP_OS, 'win') === false && file_exists('/proc/' . $pid)) {
                return false;
            }
        }

        // Try to lock
        if (false === @file_put_contents($this->getFilePath(), getmypid())) {
            throw new LockerException(sprintf('Failed to write lock file "%s"', $this->getFilePath()), LockerException::FS_WRITE);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function release()
    {
        @unlink($this->getFilePath());
    }

    /**
     * Helper function for the full path of the lock file
     *
     * @return string Absolute path to the lock file
     */
    private function getFilePath()
    {
        $lockFile = $this->lockDir . DIRECTORY_SEPARATOR . $this->id . '.lock';
        return $lockFile;
    }
}