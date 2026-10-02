<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ViaCepUnavailableException;
use App\Http\Controllers\Controller;
use App\Services\ViaCepService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CepController extends Controller
{
    public function __invoke(Request $request, string $cep, ViaCepService $viaCep): JsonResponse
    {
        $normalizedCep = preg_replace('/\D/', '', $cep);

        if ($normalizedCep === null || ! preg_match('/^\d{8}$/', $normalizedCep)) {
            return response()->json([
                'message' => 'O CEP deve conter exatamente 8 dígitos.',
            ], 400);
        }

        try {
            $address = $viaCep->find($normalizedCep);
        } catch (ViaCepUnavailableException $exception) {
            report($exception);

            return response()->json([
                'message' => 'O serviço de consulta de CEP está indisponível. Tente novamente mais tarde.',
            ], 503);
        }

        if ($address === null) {
            return response()->json([
                'message' => 'CEP não encontrado.',
            ], 404);
        }

        $street = $address['logradouro'] ?? '';
        $number = trim((string) $request->query('numero', ''));
        $enteredComplement = trim((string) $request->query('complemento', ''));
        $complement = $enteredComplement !== '' ? $enteredComplement : ($address['complemento'] ?? '');
        $neighborhood = $address['bairro'] ?? '';
        $city = $address['localidade'] ?? '';
        $state = $address['uf'] ?? '';

        return response()->json([
            'data' => [
                'cep' => $address['cep'] ?? $normalizedCep,
                'logradouro' => $street,
                'numero' => $number,
                'complemento' => $complement,
                'bairro' => $neighborhood,
                'cidade' => $city,
                'uf' => $state,
                'ddd' => $address['ddd'] ?? '',
                'endereco_completo' => $this->formatFullAddress($street, $number, $enteredComplement, $neighborhood, $city, $state),
            ],
        ]);
    }

    private function formatFullAddress(string $street, string $number, string $complement, string $neighborhood, string $city, string $state): string
    {
        if ($street === '') {
            return '';
        }

        $notEmpty = static fn (string $part): bool => $part !== '';
        $streetWithNumber = implode(', ', array_filter([$street, $number, $complement], $notEmpty));
        $location = implode(', ', array_filter([
            implode(' - ', array_filter([$streetWithNumber, $neighborhood], $notEmpty)),
            implode(' - ', array_filter([$city, $state], $notEmpty)),
        ], $notEmpty));

        return $location;
    }
}
