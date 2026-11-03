<?php

namespace App\Exceptions;

/** O usuário logado não tem permissão para a operação. */
class AcessoNegadoException extends AppException
{
    public function __construct(string $message = 'Acesso negado')
    {
        parent::__construct($message);
    }

    public function getStatusHttp(): int
    {
        return 403;
    }
}
