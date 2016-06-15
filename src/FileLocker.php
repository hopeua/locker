<?php
namespace Hope\Locker;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
     * @var array Options
     */
    private $options;

    public function __construct($id, array $options = [])
    {
        // Test ID
        if (!preg_match($this->regId, $id)) {
            throw new LockerException('Invalid ID', LockerException::INVALID_ID);
        }

        // Resolve options
        $resolver      = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->id      = $id;
        $this->options = $options;
    }

    private function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['lockDir']);
    }

    /**
     * {@inheritdoc}
     */
    public function lock()
    {
        // Check if already locked
        if ($this->isLocked()) {
            throw new LockerException('Already locked', LockerException::LOCKED);
        }

        // Try to lock
        if (false === @file_put_contents($this->getFilePath(), getmypid())) {
            throw new LockerException(sprintf('Failed to write lock file "%s"', $this->getFilePath()), LockerException::FS_WRITE);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unlock()
    {
        if ($this->isLocked()) {
            if (false === @unlink($this->getFilePath())) {
                throw new LockerException(sprintf('Failed to delete the lock file %s', $this->getFilePath()), LockerException::FS_DEL);
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
            throw new LockerException(sprintf('Failed to read the lock file %s', $this->getFilePath()), LockerException::FS_READ);
        }

        // Check if pid is valid
        if (!preg_match($this->regPid, $pid)) {
            throw new LockerException(sprintf('Unexpected content in lock file %s', $this->getFilePath()), LockerException::LOCK_CONTENT);
        }

        // Check if pid exist
        if (!@file_exists('/proc/' . $pid)) {
            return false;
        }

        return true;
    }

    /**
     * Helper function for the full path of the lock file
     *
     * @return string Absolute path to the lock file
     */
    private function getFilePath()
    {
        $lockDir  = $this->options['lockDir'];
        $lockFile = $lockDir . DIRECTORY_SEPARATOR . $this->id . '.lock';
        return $lockFile;
    }
}