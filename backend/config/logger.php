<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        // stderr aparece em `docker logs hortas_php` e nos logs do Railway
        LoggerInterface::class => function () {
            $logger = new Logger('api');
            $logger->pushHandler(new StreamHandler('php://stderr', Level::Debug));
            return $logger;
        },
    ]);
};
