<?php

namespace Acquia\Lightning\Commands;

use Robo\Tasks;
use Symfony\Component\Finder\Finder;

class RoboFile extends Tasks
{
    /**
     * Builds a task to execute a Drush command.
     *
     * @param string $command
     *   The command to execute.
     *
     * @return \Robo\Task\Base\Exec
     *   The task to execute.
     */
    protected function taskDrush ($command)
    {
        return $this->taskExec('../vendor/bin/drush')->rawArg($command)->dir('docroot');
    }

    public function install ($db_url, $profile = 'lightning')
    {
        $tasks = $this->collectionBuilder()
            ->addTask(
                $this->taskDrush('site-install')
                    ->arg($profile)
                    ->option('yes')
                    ->option('account-pass', 'admin')
                    ->option('db-url', stripslashes($db_url))
            );

        /** @var Finder $finder */
        $finder = Finder::create()->in('.')->files()->depth(0)->name('*.info.yml');

        foreach ($finder as $file)
        {
            $extension = $file->getBasename('.info.yml');
            break;
        }
        if (isset($extension) && $extension !== $profile)
        {
            $tasks->addTask(
              $this->taskDrush('pm-enable')->arg($extension)->option('yes')
            );
        }
        return $tasks;
    }
}
