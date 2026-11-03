<?php

namespace App\Exceptions;

/** A operação conflita com um dado que já existe (ex.: e-mail duplicado). */
class ConflitoException extends AppException
{
    public function __construct(string $message = 'Registro já existe')
    {
        parent::__construct($message);
    }

    public function getStatusHttp(): int
    {
        return 409;
    }
}
