<?php

namespace App\Exceptions;

use RuntimeException;

final class ViaCepUnavailableException extends RuntimeException
{
    public function __construct(public readonly string $cep, ?\Throwable $previous = null)
    {
        parent::__construct('Não foi possível consultar o serviço de CEP.', previous: $previous);
    }

    /**
     * @return array{cep: string}
     */
    public function context(): array
    {
        return ['cep' => $this->cep];
    }
}
