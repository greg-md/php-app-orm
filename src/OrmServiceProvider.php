<?php

namespace Greg\AppOrm;

use Greg\Framework\Application;
use Greg\Framework\ServiceProvider;
use Greg\Orm\Driver\DriverManager;
use Greg\Orm\Driver\MysqlDriver;
use Greg\Orm\Driver\Pdo;

class OrmServiceProvider implements ServiceProvider
{
    private const CONFIG_NAME = 'orm';

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

                    if ($type == 'mysql') {
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

            if ($defaultDriver = $this->config('default_driver') ?? null) {
                $manager->setDefaultDriverName($defaultDriver);
            }

            return $manager;
        });
    }

    public function install()
    {
        $this->app()->fire('app.config.add', __DIR__ . '/../config/config.php', self::CONFIG_NAME);
    }

    public function uninstall()
    {
        $this->app()->fire('app.config.remove', self::CONFIG_NAME);
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
