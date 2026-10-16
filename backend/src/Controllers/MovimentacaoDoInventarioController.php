<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\MovimentacaoDoInventarioService;

class MovimentacaoDoInventarioController
{
    protected MovimentacaoDoInventarioService $movimentacaoDoInventarioService;

    public function __construct(MovimentacaoDoInventarioService $movimentacaoDoInventarioService)
    {
        $this->movimentacaoDoInventarioService = $movimentacaoDoInventarioService;
    }

    public function list(Request $request, Response $response)
    {
        $movimentacoes = $this->movimentacaoDoInventarioService->findAllWhere($this->payloadUsuarioLogado($request));
        $response->getBody()->write(json_encode($movimentacoes));
        return $response->withStatus(200);
    }

    public function get(Request $request, Response $response, array $args)
    {
        $movimentacao = $this->movimentacaoDoInventarioService->findByUuid($args['uuid'], $this->payloadUsuarioLogado($request));
        $response->getBody()->write(json_encode($movimentacao));
        return $response->withStatus(200);
    }

    public function getByItem(Request $request, Response $response, array $args)
    {
        $movimentacoes = $this->movimentacaoDoInventarioService->findByItemUuid($args['uuid'], $this->payloadUsuarioLogado($request));
        $response->getBody()->write(json_encode($movimentacoes));
        return $response->withStatus(200);
    }

    public function create(Request $request, Response $response)
    {
        $data = (array)$request->getParsedBody();
        $movimentacao = $this->movimentacaoDoInventarioService->create($data, $this->payloadUsuarioLogado($request));

        $response->getBody()->write(json_encode($movimentacao));
        return $response->withStatus(201);
    }

    private function payloadUsuarioLogado(Request $request): array
    {
        return [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];
    }
}
