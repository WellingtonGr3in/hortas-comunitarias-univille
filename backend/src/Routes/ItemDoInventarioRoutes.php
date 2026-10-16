<?php

use App\Controllers\ItemDoInventarioController;
use Slim\Routing\RouteCollectorProxy;

return function (RouteCollectorProxy $app) {
    $app->group('/itens-do-inventario', function (RouteCollectorProxy $group) {
        $group->get('', ItemDoInventarioController::class . ':list');
        $group->get('/associacao/{uuid}', ItemDoInventarioController::class . ':getByAssociacao');
        $group->get('/horta/{uuid}', ItemDoInventarioController::class . ':getByHorta');
        $group->get('/{uuid}', ItemDoInventarioController::class . ':get');
        $group->post('', ItemDoInventarioController::class . ':create');
        $group->put('/{uuid}', ItemDoInventarioController::class . ':update');
        $group->delete('/{uuid}', ItemDoInventarioController::class . ':delete');
    });
};
