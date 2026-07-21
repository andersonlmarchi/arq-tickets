<?php

namespace App\Exceptions;

use Exception;

class CatalogConflictException extends Exception
{
    public function __construct(string $message = 'Nao ha ingressos disponiveis para este evento.')
    {
        parent::__construct($message);
    }
}
