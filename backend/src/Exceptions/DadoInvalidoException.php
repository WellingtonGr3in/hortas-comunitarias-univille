<?php

namespace App\Exceptions;

/** O corpo da requisição tem um valor inválido para a regra de negócio. */
class DadoInvalidoException extends AppException
{
    public function __construct(string $message = 'Dados inválidos')
    {
        parent::__construct($message);
    }

    public function getStatusHttp(): int
    {
        return 422;
    }
}
