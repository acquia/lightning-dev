<?php

namespace Acquia\Lightning\Commands;

use Composer\Installers\DrupalInstaller;
use Composer\Script\Event;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FileCommands
{
    protected static function getDestination (Event $event)
    {
        $composer = $event->getComposer();

        $package = $composer->getPackage();

        $installer = new DrupalInstaller($package, $composer, $event->getIO());

        return $installer->getInstallPath($package, 'drupal');
    }

    /**
     * Executes the 'push' script.
     *
     * @param Event $event
     *   The script event.
     */
    public static function push (Event $event)
    {
        $destination = static::getDestination($event);

        $composer = $event->getComposer();

        $archive = $composer->getArchiveManager()->archive(
            $composer->getPackage(),
            'tar',
            dirname($destination)
        );

        $file_system = new Filesystem();
        $file_system->remove($destination);
        $file_system->mkdir($destination);

        $process_executor = new ProcessExecutor($event->getIO());
        $process_executor->execute("tar -x -f $archive -C $destination");
        $file_system->remove($archive);
    }

    public static function pull (Event $event)
    {
        $source = static::getDestination($event);

        /** @var Finder $finder */
        $finder = Finder::create()->in($source)->files();

        $count = count($finder);
        if ($count === 0)
        {
            return;
        }

        $file_system = new Filesystem();

        $io = $event->getIO();
        $io->write("Copying $count file(s) from $source...");

        foreach ($finder as $file)
        {
            $path = $file->getPathname();
            $copy_to = str_replace($source, '.', $path);
            $file_system->copy($path, $copy_to);
            $io->write($path);
        }
    }

}
