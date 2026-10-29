<?php

namespace App\Exceptions;

/** E-mail ou senha incorretos no login. */
class CredenciaisInvalidasException extends AppException
{
    public function __construct(string $message = 'E-mail ou senha inválidos')
    {
        parent::__construct($message);
    }

    public function getStatusHttp(): int
    {
        return 401;
    }
}
