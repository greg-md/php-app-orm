<?php

namespace Greg\AppOrm;

use Greg\AppInstaller\Application;
use Greg\Framework\ServiceProvider;
use Greg\Orm\Connection\ConnectionManager;
use Greg\Orm\Connection\ConnectionStrategy;
use Greg\Orm\Connection\MysqlConnection;
use Greg\Support\Dir;
use Greg\Support\File;
use PHPUnit\Framework\TestCase;

class OrmServiceProviderTest extends TestCase
{
    private $rootPath = __DIR__ . '/app';

    protected function setUp(): void
    {
        Dir::make($this->rootPath);

        Dir::make($this->rootPath . '/app');
        Dir::make($this->rootPath . '/build-deploy');
        Dir::make($this->rootPath . '/config');
        Dir::make($this->rootPath . '/public');
        Dir::make($this->rootPath . '/resources');
        Dir::make($this->rootPath . '/storage');
    }

    protected function tearDown(): void
    {
        Dir::unlink($this->rootPath);
    }

    public function testCanInstantiate()
    {
        $serviceProvider = new OrmServiceProvider();

        $this->assertInstanceOf(ServiceProvider::class, $serviceProvider);
    }

    public function testCanGetName()
    {
        $serviceProvider = new OrmServiceProvider();

        $this->assertEquals('greg-orm', $serviceProvider->name());
    }

    public function testCanBoot()
    {
        $serviceProvider = new OrmServiceProvider();

        $app = new Application([
            'orm' => [
                'default_connection' => 'base',

                'connections' => [
                    'base' => [
                        'type' => 'mysql',

                        'database' => getenv('MYSQL_DATABASE') ?: 'app',
                        'host'     => getenv('MYSQL_HOST') ?: '127.0.0.1',
                        'port'     => getenv('MYSQL_PORT') ?: '3306',
                        'username' => getenv('MYSQL_USERNAME') ?: 'root',
                        'password' => getenv('MYSQL_PASSWORD') ?: '',
                        'charset'  => 'utf8',

                        'options'  => [
                            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8', // time_zone = "+02:00"
                        ],
                    ],
                ],
            ],
        ]);

        $serviceProvider->boot($app);

        /** @var ConnectionManager $manager */
        $manager = $app->get(ConnectionManager::class);

        $this->assertInstanceOf(ConnectionManager::class, $manager);

        $this->assertEquals('base', $manager->getDefaultConnectionName());

        /** @var ConnectionStrategy $connection */
        $connection = $manager->connection('base');

        $this->assertInstanceOf(ConnectionStrategy::class, $connection);

        $this->assertInstanceOf(MysqlConnection::class, $connection);
    }

    public function testCanThrowExceptionIfUndefinedConnection()
    {
        $serviceProvider = new OrmServiceProvider();

        $app = new Application([
            'orm' => [
                'connections' => [
                    'base' => [
                        'type' => 'undefined',
                    ],
                ],
            ],
        ]);

        $this->expectException(\Exception::class);

        $serviceProvider->boot($app);

        /** @var ConnectionManager $manager */
        $manager = $app->get(ConnectionManager::class);

        $manager->connection('base');
    }

    public function testCanInstall()
    {
        $serviceProvider = new OrmServiceProvider();

        $app = new Application();

        $app->configure(__DIR__ . '/app');

        $serviceProvider->install($app);

        $this->assertFileExists(__DIR__ . '/app/config/orm.php');

        $this->assertDirectoryExists(__DIR__ . '/app/resources/db');

        $this->assertFileExists(__DIR__ . '/app/phinx.php');

        $this->assertFileExists(__DIR__ . '/app/build-deploy/run/010-migration.sh');
    }

    public function testCanUninstall()
    {
        $serviceProvider = new OrmServiceProvider();

        $app = new Application();

        $app->configure(__DIR__ . '/app');

        file_put_contents(__DIR__ . '/app/config/orm.php', '');

        Dir::make(__DIR__ . '/app/resources/db');

        file_put_contents(__DIR__ . '/app/phinx.php', '');

        File::makeDir(__DIR__ . '/app/build-deploy/run/010-migration.sh');

        file_put_contents(__DIR__ . '/app/build-deploy/run/010-migration.sh', '');

        $serviceProvider->uninstall($app);

        $this->assertFileNotExists(__DIR__ . '/app/config/orm.php');

        $this->assertDirectoryNotExists(__DIR__ . '/app/resources/db');

        $this->assertFileNotExists(__DIR__ . '/app/phinx.php');

        $this->assertFileNotExists(__DIR__ . '/app/build-deploy/run/010-migration.sh');
    }
}
