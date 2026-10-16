<?php

use App\Controllers\MovimentacaoDoInventarioController;
use Slim\Routing\RouteCollectorProxy;

// Sem PUT/DELETE: movimentações são corrigidas com um ajuste, nunca editadas.
return function (RouteCollectorProxy $app) {
    $app->group('/movimentacoes-do-inventario', function (RouteCollectorProxy $group) {
        $group->get('', MovimentacaoDoInventarioController::class . ':list');
        $group->get('/item/{uuid}', MovimentacaoDoInventarioController::class . ':getByItem');
        $group->get('/{uuid}', MovimentacaoDoInventarioController::class . ':get');
        $group->post('', MovimentacaoDoInventarioController::class . ':create');
    });
};
