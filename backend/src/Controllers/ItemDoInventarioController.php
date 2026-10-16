<?php

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\ItemDoInventarioService;

class ItemDoInventarioController
{
    protected ItemDoInventarioService $itemDoInventarioService;

    public function __construct(ItemDoInventarioService $itemDoInventarioService)
    {
        $this->itemDoInventarioService = $itemDoInventarioService;
    }

    public function list(Request $request, Response $response)
    {
        $itens = $this->itemDoInventarioService->findAllWhere($this->payloadUsuarioLogado($request));
        $response->getBody()->write(json_encode($itens));
        return $response->withStatus(200);
    }

    public function get(Request $request, Response $response, array $args)
    {
        $item = $this->itemDoInventarioService->findByUuid($args['uuid'], $this->payloadUsuarioLogado($request));
        $response->getBody()->write(json_encode($item));
        return $response->withStatus(200);
    }

    public function getByAssociacao(Request $request, Response $response, array $args)
    {
        $itens = $this->itemDoInventarioService->findByAssociacaoUuid($args['uuid'], $this->payloadUsuarioLogado($request));
        $response->getBody()->write(json_encode($itens));
        return $response->withStatus(200);
    }

    public function getByHorta(Request $request, Response $response, array $args)
    {
        $itens = $this->itemDoInventarioService->findByHortaUuid($args['uuid'], $this->payloadUsuarioLogado($request));
        $response->getBody()->write(json_encode($itens));
        return $response->withStatus(200);
    }

    public function create(Request $request, Response $response)
    {
        $data = (array)$request->getParsedBody();
        $item = $this->itemDoInventarioService->create($data, $this->payloadUsuarioLogado($request));

        $response->getBody()->write(json_encode($item));
        return $response->withStatus(201);
    }

    public function update(Request $request, Response $response, array $args)
    {
        $data = (array)$request->getParsedBody();
        $item = $this->itemDoInventarioService->update($args['uuid'], $data, $this->payloadUsuarioLogado($request));

        $response->getBody()->write(json_encode($item));
        return $response->withStatus(200);
    }

    public function delete(Request $request, Response $response, array $args)
    {
        $this->itemDoInventarioService->delete($args['uuid'], $this->payloadUsuarioLogado($request));

        $response->getBody()->write(json_encode([
            "message" => "Registro UUID: " . $args['uuid'] . " excluído"
        ]));
        return $response->withStatus(200);
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
