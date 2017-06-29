<?php

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/resources/db/migrations',
        'seeds'      => '%%PHINX_CONFIG_DIR%%/resources/db/seeds',
    ],

    'environments' => [
        'default_migration_table' => 'Migrations',
        'default_database'        => 'app',

        'app' => [
            'adapter'    => 'mysql',
            'host'       => getenv('MYSQL_HOST') ?: '127.0.0.1',
            'name'       => getenv('MYSQL_DATABASE') ?: 'app',
            'user'       => getenv('MYSQL_USERNAME') ?: 'root',
            'pass'       => getenv('MYSQL_PASSWORD') ?: '',
            'port'       => getenv('MYSQL_PORT') ?: '3306',
            'charset'    => 'utf8',
        ],
    ],
];
