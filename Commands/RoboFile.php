<?php

namespace Acquia\Lightning\Commands;

use Drupal\Component\Serialization\Json;
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

    /**
     * Installs Lightning and, optionally, the developer tools.
     *
     * @param string $db_url
     *   The URL of the Drupal database.
     * @param string $profile
     *   (optional) The installation profile to use.
     * @param string $base_url
     *   (optional) The URL of the Drupal site.
     * @param array $options
     *   (optional) Additional command options.
     *
     * @option $no-dev Do not install developer tools or configure test runners.
     *
     * @return \Robo\Contract\TaskInterface
     *   The task to execute.
     */
    public function install ($db_url, $profile = 'lightning', $base_url = NULL, array $options = [
        'no-dev' => FALSE,
    ])
    {
        $site_dir = 'docroot/sites/default';

        $tasks = $this->collectionBuilder()
            ->addTask(
                $this->taskDrush('site-install')
                    ->arg($profile)
                    ->option('yes')
                    ->option('account-pass', 'admin')
                    ->option('db-url', stripslashes($db_url))
            )
            ->completion(
                $this->taskFilesystemStack()
                    ->chmod([
                        $site_dir,
                        "$site_dir/settings.php",
                    ], 0755)
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

        if ($options['no-dev'] == FALSE)
        {
            $tasks->addTask(
                $this->installDev($db_url, $base_url)
            );
        }

        return $tasks;
    }

    /**
     * Installs developer tools and configures test runners.
     *
     * @param string $db_url
     *   The URL of the Drupal database.
     * @param string $base_url
     *   (optional) The URL of the Drupal site.
     *
     * @return \Robo\Contract\TaskInterface
     *   The task to execute.
     */
    public function installDev ($db_url, $base_url = NULL)
    {
        return $this->collectionBuilder()
            ->addTask(
                $this->taskDrush('pm-enable')->arg('lightning_dev')->option('yes')
            )
            ->addTask(
                $this->configurePhpUnit($db_url, $base_url)
            )
            ->addTask(
                $this->configureBehat($base_url)
            );
    }

    /**
     * Reinstalls Drupal.
     *
     * @param string $base_url
     *   (optional) The URL of the Drupal site.
     *
     * @return \Robo\Contract\TaskInterface
     *   The task to execute.
     */
    public function reinstall ($base_url = NULL)
    {
        $info = $this->taskDrush('core:status')
            ->option('format', 'json')
            ->option('fields', 'db-driver,db-username,db-password,db-hostname,db-port,db-name,install-profile')
            ->printOutput(FALSE)
            ->run()
            ->getMessage();

        $info = Json::decode($info);

        $db_url = sprintf(
          '%s://%s%s@%s%s/%s',
          $info['db-driver'],
          $info['db-username'],
          $info['db-password'] ? ':' . $info['db-password'] : NULL,
          $info['db-hostname'],
          $info['db-port'] ? ':' . $info['db-port'] : NULL,
          $info['db-name']
        );

        return $this->install($db_url, $info['install-profile'], $base_url);
    }

    /**
     * Generates a compressed database fixture.
     *
     * @param string $version
     *   The version the fixture represents.
     *
     * @return \Robo\Contract\TaskInterface
     *   The task to execute.
     */
    public function makeFixture ($version)
    {
        return $this->collectionBuilder()
            ->addTask(
                $this->reinstall()
            )
            ->addTask(
                $this->taskDrush('sql:dump')
                    ->option('gzip')
                    ->option('result-file', "../tests/fixtures/$version.sql")
            );
    }

    /**
     * Restores a database fixture.
     *
     * @param string $version
     *   The fixture version to restore. Must exist in the tests/fixtures
     *   directory as $version.sql.gz.
     *
     * @return \Robo\Contract\TaskInterface|NULL
     *   The task to execute, or NULL if the fixture does not exist.
     */
    public function restore ($version)
    {
        $fixture = "tests/fixtures/$version.sql";

        if (file_exists("$fixture.gz"))
        {
            return $this->collectionBuilder()
                ->addTask(
                    $this->taskDrush('sql:drop')->option('yes')
                )
                ->addTask(
                    $this->taskExec('gunzip')
                        ->arg("$fixture.gz")
                        ->option('keep')
                        ->option('force')
                )
                ->addTask(
                    $this->taskDrush('sql:query')->option('file', "../$fixture")
                )
                ->completion(
                    $this->taskFilesystemStack()->remove($fixture)
                );
        }
        else
        {
            $this->say("$version fixture does not exist.");
        }
    }

    /**
     * Restores a database fixture and runs all available updates.
     *
     * @param string $from_version
     *   The fixture version to restore. Must exist in the tests/fixtures
     *   directory as $version.sql.gz.
     *
     * @return \Robo\Contract\TaskInterface|NULL
     *   The task to execute, or NULL if the fixture does not exist.
     */
    public function update ($from_version)
    {
        /** @var \Robo\Collection\CollectionBuilder $tasks */
        $tasks = $this->restore($from_version);

        if ($tasks)
        {
            return $tasks
                ->addTask(
                    $this->taskDrush('updatedb')->option('yes')
                )
                ->addTask(
                    $this->taskDrush('update:lightning')->option('no-interaction')
                );
        }
    }

    /**
     * Prepares settings.php for use with Acquia Cloud.
     *
     * @param string $subscription
     *   (optional) The Cloud subscription ID.
     * @param string $install_profile
     *   (optional) The machine name of the install profile to write to
     *   settings.php.
     *
     * @return \Robo\Contract\TaskInterface
     *   The task to execute.
     */
    public function configureCloud ($subscription = 'lightningnightly', $install_profile = 'lightning')
    {
        $settings = 'docroot/sites/default/settings.php';
        $site_dir = dirname($settings);

        // settings.php and the site directory may have received restrictive
        // permissions during installation. We need to loosen those up in order
        // to overwrite settings.php.
        $this->_chmod([$settings, $site_dir], 0755);

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
                        "\$settings['install_profile'] = '$install_profile';",
                    ])
            );
    }

    /**
     * Configures Behat.
     *
     * @param string $base_url
     *  (optional) The URL of the Drupal site.
     *
     * @return \Robo\Contract\TaskInterface
     *   The task to execute.
     */
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
     * Configures PHPUnit.
     *
     * @param string $db_url
     *   The URL of the Drupal database.
     * @param string $base_url
     *   (optional) The URL of the Drupal site.
     *
     * @return \Robo\Contract\TaskInterface
     *   The task to execute.
     *
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
