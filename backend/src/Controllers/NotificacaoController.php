<?php

namespace App\Controllers;

use App\Services\NotificacaoService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Exceptions\AcessoNegadoException;
use InvalidArgumentException;

class NotificacaoController
{
    private NotificacaoService $service;

    public function __construct(NotificacaoService $service)
    {
        $this->service = $service;
    }

    public function list(Request $request, Response $response): Response
    {
        try {
            $notificacoes = $this->service->findAll($this->payloadUsuarioLogado($request));
            $lidas = array_flip($this->service->findUuidsLidas($request->getAttribute('usuario_uuid')));
            
            $formatted = $notificacoes->map(function ($n) use ($lidas) {
                return [
                    'id' => $n->uuid,
                    'tipo' => $n->tipo,
                    'titulo' => $n->titulo,
                    'mensagem' => $n->mensagem,
                    'carteirista_uuid' => $n->carteirista_uuid,
                    'carteirista_nome' => $n->Canteirista->nome ?? null,
                    'horta_uuid' => $n->horta_uuid,
                    'horta_nome' => $n->horta->nome ?? null,
                    'data_evento' => $n->data_evento?->format('Y-m-d H:i:s'),
                    'data_inicio' => $n->data_inicio?->format('Y-m-d H:i:s'),
                    'data_fim' => $n->data_fim?->format('Y-m-d H:i:s'),
                    'ativa' => (bool) $n->ativa,
                    'prioridade' => $n->prioridade,
                    'data_criacao' => $n->data_de_criacao?->format('Y-m-d H:i:s'),
                    'lida' => isset($lidas[$n->uuid]),
                ];
            });

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $formatted
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (AcessoNegadoException $e) {
            throw $e;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function listAtivas(Request $request, Response $response): Response
    {
        try {
            $notificacoes = $this->service->findAtivas($this->payloadUsuarioLogado($request));
            $lidas = array_flip($this->service->findUuidsLidas($request->getAttribute('usuario_uuid')));
            
            $formatted = $notificacoes->map(function ($n) use ($lidas) {
                return [
                    'id' => $n->uuid,
                    'tipo' => $n->tipo,
                    'titulo' => $n->titulo,
                    'mensagem' => $n->mensagem,
                    'prioridade' => $n->prioridade,
                    'data_evento' => $n->data_evento?->format('Y-m-d H:i:s'),
                    'horta_nome' => $n->horta->nome ?? null,
                    'lida' => isset($lidas[$n->uuid]),
                ];
            });

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $formatted
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (AcessoNegadoException $e) {
            throw $e;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $notificacao = $this->service->findByUuid($args['id'], $this->payloadUsuarioLogado($request));
            
            $data = [
                'id' => $notificacao->uuid,
                'tipo' => $notificacao->tipo,
                'titulo' => $notificacao->titulo,
                'mensagem' => $notificacao->mensagem,
                'carteirista_uuid' => $notificacao->carteirista_uuid,
                'carteirista_nome' => $notificacao->Canteirista->nome ?? null,
                'horta_uuid' => $notificacao->horta_uuid,
                'horta_nome' => $notificacao->horta->nome ?? null,
                'data_evento' => $notificacao->data_evento?->format('Y-m-d H:i:s'),
                'data_inicio' => $notificacao->data_inicio?->format('Y-m-d H:i:s'),
                'data_fim' => $notificacao->data_fim?->format('Y-m-d H:i:s'),
                'ativa' => (bool) $notificacao->ativa,
                'prioridade' => $notificacao->prioridade,
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
        } catch (AcessoNegadoException $e) {
            throw $e;
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
            $notificacao = $this->service->create($data, $this->payloadUsuarioLogado($request));

            $responseData = [
                'id' => $notificacao->uuid,
                'tipo' => $notificacao->tipo,
                'titulo' => $notificacao->titulo,
                'mensagem' => $notificacao->mensagem,
                'data_inicio' => $notificacao->data_inicio?->format('Y-m-d H:i:s'),
                'data_fim' => $notificacao->data_fim?->format('Y-m-d H:i:s'),
                'ativa' => (bool) $notificacao->ativa,
                'prioridade' => $notificacao->prioridade,
            ];

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Notificação criada com sucesso',
                'data' => $responseData
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (AcessoNegadoException $e) {
            throw $e;
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
            $notificacao = $this->service->update($args['id'], $data, $this->payloadUsuarioLogado($request));

            $responseData = [
                'id' => $notificacao->uuid,
                'tipo' => $notificacao->tipo,
                'titulo' => $notificacao->titulo,
                'mensagem' => $notificacao->mensagem,
                'data_inicio' => $notificacao->data_inicio?->format('Y-m-d H:i:s'),
                'data_fim' => $notificacao->data_fim?->format('Y-m-d H:i:s'),
                'ativa' => (bool) $notificacao->ativa,
                'prioridade' => $notificacao->prioridade,
            ];

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Notificação atualizada com sucesso',
                'data' => $responseData
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (AcessoNegadoException $e) {
            throw $e;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function marcarLida(Request $request, Response $response, array $args): Response
    {
        try {
            $this->service->marcarComoLida($args['id'], $this->payloadUsuarioLogado($request));

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Notificação marcada como lida'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }

    public function marcarTodasLidas(Request $request, Response $response): Response
    {
        $marcadas = $this->service->marcarTodasComoLidas($this->payloadUsuarioLogado($request));

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Notificações marcadas como lidas',
            'data' => ['marcadas' => $marcadas]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $this->service->delete($args['id'], $this->payloadUsuarioLogado($request));
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Notificação excluída com sucesso'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (AcessoNegadoException $e) {
            throw $e;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
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
