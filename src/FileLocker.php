<?php
namespace Hope\Locker;

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
        $id = $this->normalizeId($id);

        if ($lockDir) {
            if (!is_dir($lockDir) || !is_writable($lockDir)) {
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
        if ($this->isLocked()) {
            throw new LockerException('Already locked', LockerException::LOCKED);
        }

        // Try to lock
        if (false === @file_put_contents($this->getFilePath(), getmypid())) {
            throw new LockerException(sprintf(
                'Failed to write lock file "%s"',
                $this->getFilePath()
            ), LockerException::FS_WRITE);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function release()
    {
        if ($this->isLocked()) {
            if (!@unlink($this->getFilePath())) {
                throw new LockerException('Can\'t remove lockfile', LockerException::FS_DEL);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isLocked()
    {
        // Check the lock file
        if (!file_exists($this->getFilePath())) {
            return false;
        }

        // Get pid of last process
        $pid = @file_get_contents($this->getFilePath());
        if (false === $pid) {
            throw new LockerException(sprintf(
                'Failed to read the lock file %s',
                $this->getFilePath()
            ), LockerException::FS_READ);
        }

        // Check if pid is valid
        if (!preg_match($this->regPid, $pid)) {
            throw new LockerException(sprintf(
                'Unexpected content in lock file %s',
                $this->getFilePath()
            ), LockerException::LOCK_CONTENT);
        }

        // Check if process exists
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $runningPIDs = array_column(
                array_map(
                    'str_getcsv',
                    explode("\n", trim(`tasklist /FO csv /NH`))
                ),
                1
            );
        } else {
            $runningPIDs = explode("\n", trim(`ps -e | awk '{print $1}'`));
        }

        if (in_array($pid, $runningPIDs)) {
            return true;
        }

        return false;
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
    private function normalizeId($id)
    {
        return preg_replace(
            '/[_]+/',
            '_',
            preg_replace(
                '/[^_0-9a-z.-]/',
                '_',
                strtolower($id)
            )
        );
    }
}
