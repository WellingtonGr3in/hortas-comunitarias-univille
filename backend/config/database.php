<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;

return function(ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        Capsule::class => function () {
            $capsule = new Capsule;

            $capsule->addConnection([
                'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
                'host'      => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port'      => $_ENV['DB_PORT'] ?? '3306',
                'database'  => $_ENV['DB_NAME'] ?? 'test',
                'username'  => $_ENV['DB_USER'] ?? 'root',
                'password'  => $_ENV['DB_PASS'] ?? '',
                'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
                'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ]);

            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            return $capsule;
        }
    ]);
};
