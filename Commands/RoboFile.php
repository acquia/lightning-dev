<?php

namespace Acquia\Lightning\Commands;

use Drupal\Component\Utility\NestedArray;
use Robo\Tasks;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class RoboFile extends Tasks
{

    /**
     * The default URL of the Drupal site.
     *
     * @var string
     */
    const BASE_URL = 'http://127.0.0.1';

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

    public function configureBehat ($base_url = NULL)
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
                    $base_url ?: static::BASE_URL,
                    'docroot',
                ],
                Yaml::dump($configuration)
            );

            return $this->taskWriteToFile('.behat.yml')->text($configuration);
        }
    }

    /**
     * @command configure:phpunit
     */
    public function configurePhpUnit ($db_url, $base_url = NULL)
    {
        $conf = 'docroot/core/phpunit.xml';

        return $this->collectionBuilder()
            ->addTask(
                $this->taskFilesystemStack()
                    ->copy("$conf.dist", $conf, TRUE)
                    ->mkdir([
                        'docroot/modules',
                        'docroot/profiles',
                        'docroot/themes',
                    ])
            )
            ->addTask(
                $this->taskReplaceInFile($conf)
                    ->from([
                        '<env name="SIMPLETEST_DB" value=""/>',
                        '<env name="SIMPLETEST_BASE_URL" value=""/>',
                    ])
                    ->to([
                        '<env name="SIMPLETEST_DB" value="' . stripslashes($db_url) . '"/>',
                        '<env name="SIMPLETEST_BASE_URL" value="' . ($base_url ?: static::BASE_URL) . '"/>',
                    ])
            );
    }
}
