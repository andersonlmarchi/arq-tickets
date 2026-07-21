<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class CatalogUnavailableException extends Exception
{
    public function __construct(
        string $message = 'Nao foi possivel concluir a compra agora. Tente novamente em instantes.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function from(Throwable $previous): self
    {
        return new self(previous: $previous);
    }
}
