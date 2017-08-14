<?php

namespace Greg\AppOrm;

use Greg\AppInstaller\Application;
use Greg\AppInstaller\Events\BuildDeployRunAddEvent;
use Greg\AppInstaller\Events\BuildDeployRunRemoveEvent;
use Greg\AppInstaller\Events\ConfigAddEvent;
use Greg\AppInstaller\Events\ConfigRemoveEvent;
use Greg\AppInstaller\Events\ResourceAddEvent;
use Greg\AppInstaller\Events\ResourceRemoveEvent;
use Greg\AppInstaller\Events\RootAddEvent;
use Greg\AppInstaller\Events\RootRemoveEvent;
use Greg\Framework\ServiceProvider;
use Greg\Orm\Driver\DriverManager;
use Greg\Orm\Driver\MysqlDriver;
use Greg\Orm\Driver\Pdo;

class OrmServiceProvider implements ServiceProvider
{
    const TYPE_MYSQL = 'mysql';

    private const CONFIG_NAME = 'orm';

    private const RESOURCE_DB_PATH = 'db';

    private const PHINX_CONFIG_NAME = 'phinx.php';

    private const BUILD_DEPLOY_RUN_NAME = '010-migration.sh';

    private $app;

    public function name()
    {
        return 'greg-orm';
    }

    public function boot(Application $app)
    {
        $this->app = $app;

        $app->inject(DriverManager::class, function () {
            $manager = new DriverManager();

            foreach ((array) $this->config('drivers') as $name => $credentials) {
                $manager->register($name, function () use ($name, $credentials) {
                    $type = $credentials['type'] ?? null;

                    if ($type == self::TYPE_MYSQL) {
                        return new MysqlDriver(
                            new Pdo(
                                'mysql:dbname=' . ($credentials['database'] ?? 'app')
                                . ';host=' . ($credentials['host'] ?? '127.0.0.1')
                                . ';port=' . ($credentials['port'] ?? 3306),
                                $credentials['username'] ?? 'root',
                                $credentials['password'] ?? '',
                                $credentials['options'] ?? []
                            )
                        );
                    }

                    throw new \Exception('Unsupported ORM driver type `' . $type . '` for `' . $name . '` strategy.');
                });
            }

            if ($defaultDriver = $this->config('default_driver')) {
                $manager->setDefaultDriverName($defaultDriver);
            }

            return $manager;
        });
    }

    public function install(Application $app)
    {
        $app->event(new ConfigAddEvent(__DIR__ . '/../config/config.php', self::CONFIG_NAME));

        $app->event(new ResourceAddEvent(__DIR__ . '/../resources/db', self::RESOURCE_DB_PATH));

        $app->event(new RootAddEvent(__DIR__ . '/../phinx.php', self::PHINX_CONFIG_NAME));

        $app->event(new BuildDeployRunAddEvent(__DIR__ . '/../build-deploy/run.sh', self::BUILD_DEPLOY_RUN_NAME));
    }

    public function uninstall(Application $app)
    {
        $app->event(new ConfigRemoveEvent(self::CONFIG_NAME));

        $app->event(new ResourceRemoveEvent(self::RESOURCE_DB_PATH));

        $app->event(new RootRemoveEvent(self::PHINX_CONFIG_NAME));

        $app->event(new BuildDeployRunRemoveEvent(self::BUILD_DEPLOY_RUN_NAME));
    }

    private function config(string $name)
    {
        return $this->app()->config(self::CONFIG_NAME . '.' . $name);
    }

    private function app(): Application
    {
        return $this->app;
    }
}
