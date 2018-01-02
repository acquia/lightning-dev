<?php

use Composer\Installers\DrupalInstaller;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class Lightning
{
    /**
     * The destination directory.
     *
     * @var string
     */
    protected $destination;

    /**
     * The I/O handler.
     *
     * @var IOInterface
     */
    protected $io;

    /**
     * Lightning constructor.
     *
     * @param string $destination
     *   The destination directory.
     * @param IOInterface $io
     *   The I/O handler.
     */
    public function __construct ($destination, IOInterface $io)
    {
        $this->destination = $destination;
        $this->io = $io;
    }

    /**
     * Creates a DrupalInstaller for the root package.
     *
     * @param Event $event
     * @return DrupalInstaller
     */
    protected static function getInstaller(Event $event)
    {
        $composer = $event->getComposer();

        return new DrupalInstaller(
            $composer->getPackage(),
            $composer,
            $event->getIO()
        );
    }

    /**
     * Executes the 'push' script.
     *
     * @param Event $event
     *   The script event.
     */
    public static function push (Event $event)
    {
        $package = $event->getComposer()->getPackage();

        $handler = new static(
            static::getInstaller($event)->getInstallPath($package, 'drupal'),
            $event->getIO()
        );
        $handler->doPush();
    }

    protected function doPush ()
    {
        $file_system = new Filesystem();
        $file_system->mkdir($this->destination);

        $finder = new Finder();
        $finder
            ->in('.')
            ->exclude(['docroot', 'vendor'])
            ->ignoreDotFiles(TRUE)
            ->ignoreVCS(TRUE);

        $count = count($finder);
        if ($count === 0)
        {
            return;
        }

        $this->io->write("Copying $count file(s) to $this->destination...");

        foreach ($finder as $file)
        {
            $path = $file->getPathname();
            // Replace the initial ./ with the destination path.
            $copy_to = preg_replace('/^\.\//', $this->destination . '/', $path);
            $file_system->copy($path, $copy_to);
            $this->io->write($path);
        }
    }
}
