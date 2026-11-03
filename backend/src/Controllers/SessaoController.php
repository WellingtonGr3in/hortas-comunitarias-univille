<?php
namespace App\Controllers;

use App\Services\SessaoService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SessaoController
{   
    private SessaoService $sessaoService;

    public function __construct(SessaoService $sessaoService){
        $this->sessaoService = $sessaoService;
    }

    public function signIn(Request $request, Response $response)
    {
        $payloadUsuarioLogado = [
            'usuario_uuid' => $request->getAttribute('usuario_uuid'),
            'cargo_uuid' => $request->getAttribute('cargo_uuid'),
            'associacao_uuid' => $request->getAttribute('associacao_uuid'),
            'horta_uuid' => $request->getAttribute('horta_uuid'),
        ];

        $data = (array)$request->getParsedBody();

        // Credencial errada vira 401 (CredenciaisInvalidasException) no FormatadorDeErrosMiddleware;
        // qualquer outra falha segue o fluxo normal e responde 500
        $token = $this->sessaoService->signIn((string) ($data['email'] ?? ''), (string) ($data['senha'] ?? ''), $payloadUsuarioLogado);
        $response->getBody()->write(json_encode(['token' => $token]));

        return $response;
    }
    public function signUp(Request $request, Response $response)
    {
        $data = (array)$request->getParsedBody();
        
        
        $cadastroCriado = $this->sessaoService->signUp($data);
        $response->getBody()->write(json_encode($cadastroCriado));
        
        return $response->withStatus(200);
    }
}
