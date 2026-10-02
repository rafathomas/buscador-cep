<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CepLookupTest extends TestCase
{
    public function test_it_returns_an_address_for_a_valid_cep(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::response([
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'complemento' => 'lado ímpar',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
                'ibge' => '3550308',
                'ddd' => '11',
            ]),
        ]);

        $response = $this->getJson('/api/cep/01001-000?numero=340&complemento=Sala%202');

        $response->assertOk()->assertJsonPath('data.cidade', 'São Paulo')
            ->assertJsonPath('data.logradouro', 'Praça da Sé')
            ->assertJsonPath('data.complemento', 'Sala 2')
            ->assertJsonPath('data.endereco_completo', 'Praça da Sé, 340, Sala 2 - Sé, São Paulo - SP')
            ->assertJsonMissingPath('data.ibge');
        Http::assertSent(fn ($request) => $request->url() === 'https://viacep.com.br/ws/01001000/json/');
    }

    public function test_it_returns_bad_request_for_an_invalid_cep(): void
    {
        $this->getJson('/api/cep/123')->assertStatus(400)
            ->assertJsonPath('message', 'O CEP deve conter exatamente 8 dígitos.');
    }

    public function test_it_returns_not_found_when_viacep_cannot_find_the_cep(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://viacep.com.br/*' => Http::response(['erro' => true])]);

        $this->getJson('/api/cep/99999999')->assertNotFound()
            ->assertJsonPath('message', 'CEP não encontrado.');
    }

    public function test_it_keeps_the_complete_address_empty_when_there_is_no_street(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::response([
                'cep' => '01001-000',
                'logradouro' => '',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ]),
        ]);

        $this->getJson('/api/cep/01001000?numero=340&complemento=Sala%202')
            ->assertOk()
            ->assertJsonPath('data.logradouro', '')
            ->assertJsonPath('data.endereco_completo', '');
    }

    public function test_it_returns_service_unavailable_when_viacep_fails(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://viacep.com.br/ws/01001000/json/' => Http::response([], 500)]);

        $this->getJson('/api/cep/01001000')->assertStatus(503)
            ->assertJsonPath('message', 'O serviço de consulta de CEP está indisponível. Tente novamente mais tarde.');
    }

    public function test_the_home_page_is_available(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Número')
            ->assertSee('Complemento')
            ->assertSee('Endereço completo')
            ->assertSee('Copiar endereço')
            ->assertSee('Copiado!')
            ->assertDontSee('IBGE');
    }
}
