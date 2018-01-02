<?php

namespace Acquia\Lightning\Commands;

use Composer\Installers\DrupalInstaller;
use Composer\Script\Event;
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

        $file_system = new Filesystem();
        $file_system->mkdir($destination);

        /** @var Finder $finder */
        $finder = Finder::create()
            ->files()
            ->in('.')
            ->exclude(['docroot', 'vendor'])
            ->ignoreVCS(TRUE)
            ->ignoreDotFiles(TRUE);

        $count = count($finder);
        if ($count === 0)
        {
            return;
        }

        $io = $event->getIO();
        $io->write("Copying $count file(s) to $destination...");

        foreach ($finder as $file)
        {
            $path = $file->getPathname();
            // Replace the initial ./ with the destination path.
            $copy_to = preg_replace('/^\.\//', $destination . '/', $path);
            $file_system->copy($path, $copy_to);
            $io->write($path);
        }
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
