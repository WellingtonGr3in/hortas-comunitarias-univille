<?php

namespace App\Controllers;

use App\Services\PagamentoService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use InvalidArgumentException;

class PagamentoController
{
    private PagamentoService $service;

    public function __construct(PagamentoService $service)
    {
        $this->service = $service;
    }

    public function list(Request $request, Response $response): Response
    {
        $pagamentos = $this->service->findAll($this->payloadUsuarioLogado($request));

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $pagamentos->map(fn ($p) => $this->formatar($p))
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function registrarMensalidade(Request $request, Response $response): Response
    {
        $pagamento = $this->service->registrarPagamentoDeMensalidade(
            (array) $request->getParsedBody(),
            $this->payloadUsuarioLogado($request)
        );

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Pagamento registrado e mensalidade quitada',
            'data' => $this->formatar($pagamento)
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $pagamento = $this->service->findByUuid($args['id']);
            
            $data = [
                'id' => $pagamento->uuid,
                'carteirista_uuid' => $pagamento->carteirista_uuid,
                'carteirista_nome' => $pagamento->Canteirista->nome ?? '',
                'carteirista_telefone' => $pagamento->Canteirista->telefone ?? '',
                'valor' => (float) $pagamento->valor,
                'forma_pagamento' => $pagamento->forma_pagamento,
                'data_pagamento' => $pagamento->data_pagamento?->format('Y-m-d'),
                'observacao' => $pagamento->observacao,
                'data_criacao' => $pagamento->data_de_criacao?->format('Y-m-d H:i:s'),
            ];

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $data
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $pagamento = $this->service->create($data);

            $responseData = [
                'id' => $pagamento->uuid,
                'carteirista_uuid' => $pagamento->carteirista_uuid,
                'valor' => (float) $pagamento->valor,
                'forma_pagamento' => $pagamento->forma_pagamento,
                'data_pagamento' => $pagamento->data_pagamento?->format('Y-m-d'),
                'observacao' => $pagamento->observacao,
            ];

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Pagamento criado com sucesso',
                'data' => $responseData
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $request->getParsedBody();
            $pagamento = $this->service->update($args['id'], $data);

            $responseData = [
                'id' => $pagamento->uuid,
                'carteirista_uuid' => $pagamento->carteirista_uuid,
                'valor' => (float) $pagamento->valor,
                'forma_pagamento' => $pagamento->forma_pagamento,
                'data_pagamento' => $pagamento->data_pagamento?->format('Y-m-d'),
                'observacao' => $pagamento->observacao,
            ];

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Pagamento atualizado com sucesso',
                'data' => $responseData
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $this->service->estornar($args['id'], $this->payloadUsuarioLogado($request));

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Pagamento estornado'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function formatar($p): array
    {
        $mensalidade = $p->mensalidade();

        return [
            'id' => $p->uuid,
            'carteirista_uuid' => $p->carteirista_uuid,
            'carteirista_nome' => $p->Canteirista->nome ?? '',
            'carteirista_telefone' => $p->Canteirista->telefone ?? '',
            'mensalidade_uuid' => $p->mensalidade_uuid,
            'mensalidade_tipo' => $p->mensalidade_tipo,
            'mensalidade_vencimento' => $mensalidade?->data_vencimento,
            'mensalidade_pagador' => $mensalidade?->usuario?->nome_completo,
            'valor' => (float) $p->valor,
            'forma_pagamento' => $p->forma_pagamento,
            'data_pagamento' => $p->data_pagamento?->format('Y-m-d'),
            'observacao' => $p->observacao,
            'data_criacao' => $p->data_de_criacao?->format('Y-m-d H:i:s'),
        ];
    }

    private function payloadUsuarioLogado(Request $request): array
    {
        return [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'cargo_slug' => $request->getAttribute('cargo_slug'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];
    }
}
