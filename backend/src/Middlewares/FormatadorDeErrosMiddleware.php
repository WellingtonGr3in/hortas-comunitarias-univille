<?php

namespace App\Middlewares;

use App\Exceptions\AppException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Exceptions\ValidationException;
use Slim\Exception\HttpException;
use Throwable;

class FormatadorDeErrosMiddleware
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (NestedValidationException $e) {
            return $this->responder(422, [
                'error' => 'Erro de validação de campos',
                'details' => $e->getMessages(),
            ]);
        } catch (ValidationException $e) {
            // ->check() do Respect para no primeiro campo inválido e lança só a ValidationException
            return $this->responder(422, ['error' => $e->getMessage()]);
        } catch (AppException $e) {
            return $this->responder($e->getStatusHttp(), ['error' => $e->getMessage()]);
        } catch (HttpException $e) {
            return $this->responder($e->getCode(), ['error' => $e->getMessage()]);
        } catch (\InvalidArgumentException $e) {
            // Legado: alguns services ainda lançam InvalidArgumentException para dado inválido.
            // Migrar para DadoInvalidoException e remover este ramo.
            return $this->responder(422, ['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            // Erro não previsto: o detalhe (SQL, arquivo, stack) fica só no log do servidor
            $erroId = bin2hex(random_bytes(4));
            $this->logger->error($e->getMessage(), [
                'erro_id' => $erroId,
                'metodo' => $request->getMethod(),
                'rota' => $request->getUri()->getPath(),
                'exception' => $e,
            ]);

            return $this->responder(500, [
                'error' => 'Erro interno do servidor',
                'erro_id' => $erroId,
            ]);
        }
    }

    private function responder(int $status, array $corpo): Response
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode($corpo + ['status' => $status], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
