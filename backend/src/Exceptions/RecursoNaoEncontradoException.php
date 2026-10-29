<?php

namespace App\Exceptions;

/** O recurso não existe ou o usuário logado não pode vê-lo. */
class RecursoNaoEncontradoException extends AppException
{
    public function __construct(string $message = 'Recurso não encontrado')
    {
        parent::__construct($message);
    }

    public function getStatusHttp(): int
    {
        return 404;
    }
}
