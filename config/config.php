<?php

return [
    'default_connection' => 'base',

    'connections' => [
        'base' => [
            'type' => \Greg\AppOrm\OrmServiceProvider::TYPE_MYSQL,

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
];
