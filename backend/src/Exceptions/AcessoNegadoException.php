<?php

namespace App\Exceptions;

use Exception;

// Usuário logado tentando ver ou mudar algo fora do que o cargo dele permite (HTTP 403).
class AcessoNegadoException extends Exception
{
    public function __construct(string $message = 'Acesso negado')
    {
        parent::__construct($message, 403);
    }
}
