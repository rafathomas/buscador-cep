# API CEP

Aplicação Laravel 13 para consultar endereços brasileiros a partir do CEP. A consulta é feita no [ViaCEP](https://viacep.com.br/) e está disponível tanto por uma API JSON quanto por uma página web em Blade.

## Funcionalidades

- Consulta de endereço por CEP com integração ao ViaCEP.
- Validação de CEP com retorno `400` para formato inválido e `404` quando o CEP não existe.
- Página web responsiva com campos para CEP, número e complemento.
- Campo de endereço completo pronto para copiar.
- Testes automatizados sem chamadas reais ao ViaCEP.

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) em execução.
- PHP 8.4+ e Composer, apenas para a primeira instalação das dependências.

## Executar localmente

1. Instale as dependências e crie o arquivo de ambiente:

   ```bash
   composer install
   cp .env.example .env
   ```

2. Suba os containers do Laravel Sail:

   ```bash
   ./vendor/bin/sail up -d
   ```

3. Gere a chave da aplicação e execute as migrations:

   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate
   ```

4. Abra [http://localhost](http://localhost) no navegador.

Para encerrar os containers:

```bash
./vendor/bin/sail down
```

## API

### Consultar CEP

```http
GET /api/cep/{cep}?numero={numero}&complemento={complemento}
```

- `cep`: obrigatório; aceita oito dígitos, com ou sem hífen.
- `numero`: opcional na API e obrigatório na página web.
- `complemento`: opcional. Quando informado, é usado na montagem do endereço completo.

Exemplo:

```bash
curl 'http://localhost/api/cep/01001-000?numero=340&complemento=Sala%202'
```

Resposta de sucesso (`200`):

```json
{
  "data": {
    "cep": "01001-000",
    "logradouro": "Praça da Sé",
    "numero": "340",
    "complemento": "Sala 2",
    "bairro": "Sé",
    "cidade": "São Paulo",
    "uf": "SP",
    "ddd": "11",
    "endereco_completo": "Praça da Sé, 340, Sala 2 - Sé, São Paulo - SP"
  }
}
```

### Erros

| Situação | Status | Resposta |
| --- | --- | --- |
| CEP fora do formato esperado | `400` | `O CEP deve conter exatamente 8 dígitos.` |
| CEP não encontrado | `404` | `CEP não encontrado.` |
| ViaCEP indisponível | `503` | Mensagem para tentar novamente mais tarde. |

## Testes

Rode toda a suíte com Sail:

```bash
./vendor/bin/sail artisan test
```

Ou, com PHP disponível localmente:

```bash
php artisan test
```
