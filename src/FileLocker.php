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
        $id = $this->canonicalizeId($id);

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

            // on windows we will return false if the file exists
            // on linux we will make another check where we check if the process file exists
            if (stripos(PHP_OS, 'win') === false) {
                return false;
            } elseif(file_exists('/proc/' . $pid)) {
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

            // if the current pid equals our pid we can delete the lock file and thereby release the lock
            if($pid === getmypid()) {
                @unlink($this->getFilePath());
            }
        }
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

    /**
     * @param string $id
     * @return string
     */
    private function canonicalizeId($id) {
        return preg_replace('/[_]+/', '_', preg_replace('/[^_0-9a-z.-]/', '_', strtolower($id)));
    }
}