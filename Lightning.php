<?php

use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class Lightning
{
    /**
     * The root package.
     *
     * @var RootPackageInterface
     */
    protected $package;

    /**
     * The I/O handler.
     *
     * @var IOInterface
     */
    protected $io;

    /**
     * Lightning constructor.
     *
     * @param RootPackageInterface $package
     *   The root package.
     * @param IOInterface $io
     *   The I/O handler.
     */
    public function __construct (RootPackageInterface $package, IOInterface $io)
    {
        $this->package = $package;
        $this->io = $io;
    }

    /**
     * Executes the 'push' script.
     *
     * @param Event $event
     *   The script event.
     */
    public static function push (Event $event)
    {
        $handler = new static(
            $event->getComposer()->getPackage(),
            $event->getIO()
        );
        $handler->doPush();
    }

    protected function doPush ()
    {
        $extra = $this->package->getExtra();

        $destination = NULL;
        foreach ($extra['installer-paths'] as $path => $criteria)
        {
            if (in_array('type:' . $this->package->getType(), $criteria, TRUE) || in_array($this->package->getName(), $criteria, TRUE))
            {
                $destination = $path;
                break;
            }
        }
        if (empty($destination))
        {
            return $this->io->writeError("Could not determine the destination directory.");
        }

        list (, $extension) = explode('/', $this->package->getName(), 2);
        $destination = str_replace('{$name}', $extension, $destination);

        $file_system = new Filesystem();
        $file_system->mkdir($destination);

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

        $this->io->write("Copying $count file(s) to $destination...");

        foreach ($finder as $file)
        {
            $path = $file->getPathname();
            // Replace the initial ./ with the destination path.
            $copy_to = preg_replace('/^\.\//', "$destination/", $path);
            $file_system->copy($path, $copy_to);
            $this->io->write($path);
        }
    }
}
