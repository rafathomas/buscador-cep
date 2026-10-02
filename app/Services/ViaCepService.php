<?php

namespace App\Services;

use App\Exceptions\ViaCepUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class ViaCepService
{
    /**
     * @return array{cep?: string, logradouro?: string, complemento?: string, bairro?: string, localidade?: string, uf?: string, ibge?: string, ddd?: string}|null
     *
     * @throws ViaCepUnavailableException
     */
    public function find(string $cep): ?array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(5)
                ->retry([100, 200], 0, function (Throwable $exception): bool {
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException
                            && ($exception->response->serverError() || $exception->response->status() === 429));
                }, throw: false)
                ->get("https://viacep.com.br/ws/{$cep}/json/");
        } catch (ConnectionException $exception) {
            throw new ViaCepUnavailableException($cep, $exception);
        }

        if ($response->failed()) {
            throw new ViaCepUnavailableException($cep);
        }

        $address = $response->json();

        if (! is_array($address) || ($address['erro'] ?? false) === true) {
            return null;
        }

        /** @var array{cep?: string, logradouro?: string, complemento?: string, bairro?: string, localidade?: string, uf?: string, ibge?: string, ddd?: string} $address */
        return $address;
    }
}
