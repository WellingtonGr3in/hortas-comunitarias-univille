<?php

use DI\ContainerBuilder;
use App\Models\ItemDoInventarioModel;
use App\Models\MovimentacaoDoInventarioModel;
use App\Repositories\ItemDoInventarioRepository;
use App\Repositories\MovimentacaoDoInventarioRepository;
use App\Services\ItemDoInventarioService;
use App\Services\MovimentacaoDoInventarioService;
use App\Controllers\ItemDoInventarioController;
use App\Controllers\MovimentacaoDoInventarioController;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        ItemDoInventarioModel::class => DI\autowire(ItemDoInventarioModel::class),
        ItemDoInventarioRepository::class => DI\autowire(ItemDoInventarioRepository::class),
        ItemDoInventarioService::class => DI\autowire(ItemDoInventarioService::class),
        ItemDoInventarioController::class => DI\autowire(ItemDoInventarioController::class),
        MovimentacaoDoInventarioModel::class => DI\autowire(MovimentacaoDoInventarioModel::class),
        MovimentacaoDoInventarioRepository::class => DI\autowire(MovimentacaoDoInventarioRepository::class),
        MovimentacaoDoInventarioService::class => DI\autowire(MovimentacaoDoInventarioService::class),
        MovimentacaoDoInventarioController::class => DI\autowire(MovimentacaoDoInventarioController::class),
    ]);
};
