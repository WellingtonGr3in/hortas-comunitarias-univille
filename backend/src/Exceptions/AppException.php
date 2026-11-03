<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Erro previsto pela regra de negócio. A mensagem é escrita por nós e pode ir para o cliente;
 * o FormatadorDeErrosMiddleware responde com o status HTTP de cada subclasse.
 */
abstract class AppException extends RuntimeException
{
    abstract public function getStatusHttp(): int;
}
