<?php

namespace Acquia\Lightning\Commands;

use Drupal\Component\Utility\NestedArray;
use Robo\Tasks;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

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
        return $this->taskExec('vendor/bin/drush')->rawArg($command);
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
            $info = Yaml::parse($file->getContents());
            break;
        }

        if (isset($extension, $info) && $extension !== $profile)
        {
          $install = isset($info['components']) ? $info['components'] : [];
          array_push($install, $extension);

          $tasks->addTask(
            $this->taskDrush('pm-enable')->args($install)->option('yes')
          );
        }
        return $tasks;
    }

    public function configureCloud ($subscription = 'lightningnightly')
    {
        $settings = 'docroot/sites/default/settings.php';
        $site_dir = dirname($settings);

        return $this->collectionBuilder()
            ->addTask(
                $this->taskFilesystemStack()
                    ->mkdir('config/default')
                    ->touch('config/default/.gitkeep')
                    ->copy("$site_dir/default.settings.php", $settings, TRUE)
            )
            ->addTask(
                $this->taskWriteToFile($settings)
                    ->append()
                    ->lines([
                        "if (file_exists('/var/www/site-php')) {",
                        "  require '/var/www/site-php/$subscription/$subscription-settings.inc';",
                        "}",
                    ])
            );
    }

    public function configureBehat ($base_url = 'http://127.0.0.1')
    {
        $configuration = [];

        /** @var Finder $partials */
        $partials = Finder::create()
            ->in('docroot')
            ->files()
            ->name('behat.partial.yml');

        foreach ($partials as $partial)
        {
            $partial = str_replace(
                '%paths.base%',
                $partial->getPathInfo()->getRealPath(),
                $partial->getContents()
            );

            $configuration = NestedArray::mergeDeep($configuration, Yaml::parse($partial));
        }

        if ($configuration)
        {
            $configuration = str_replace(
                [
                    '%base_url%',
                    '%drupal_root%',
                ],
                [
                    $base_url,
                    'docroot',
                ],
                Yaml::dump($configuration)
            );

            return $this->taskWriteToFile('.behat.yml')->text($configuration);
        }
    }
}
