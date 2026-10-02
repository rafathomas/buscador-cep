<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de CEP</title>
    <style>
        :root {
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            color: #172033;
            background: #f5f7fb;
        }

        body {
            display: grid;
            min-height: 100vh;
            margin: 0;
            place-items: center;
        }

        main {
            width: min(100% - 2rem, 840px);
        }

        .card {
            padding: 2rem;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 16px 40px #16265414;
        }

        h1 {
            margin: 0 0 .5rem;
            font-size: 1.75rem;
        }

        p {
            color: #566075;
        }

        form {
            display: grid;
            grid-template-columns: minmax(9rem, 1fr) minmax(6rem, .55fr) minmax(12rem, 1.25fr) auto;
            gap: .75rem;
            margin-top: 1.5rem;
        }

        input,
        button {
            min-height: 2.8rem;
            border-radius: .5rem;
            font: inherit;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        input {
            min-width: 0;
            padding: 0 .8rem;
            border: 1px solid #c8d0df;
        }

        button {
            padding: 0 1rem;
            border: 0;
            color: white;
            background: #2156d9;
            cursor: pointer;
            white-space: nowrap;
        }

        button:disabled {
            cursor: wait;
            opacity: .65;
        }

        #copy-address {
            transition: background-color .2s ease, transform .2s ease;
        }

        #copy-address.is-copied {
            background: #16803c;
            animation: copied .45s ease;
        }

        @keyframes copied {
            50% { transform: scale(1.06); }
        }

        #message {
            min-height: 1.5rem;
            margin: 1rem 0 0;
        }

        .error {
            color: #ba1a1a;
        }

        dl {
            display: grid;
            grid-template-columns: 9rem 1fr;
            gap: .7rem 1rem;
            margin: 1.5rem 0 0;
        }

        dt {
            font-weight: 700;
            color: #45516b;
        }

        dd {
            margin: 0;
        }

        .complete-address {
            margin-top: 1.25rem;
        }

        .complete-address label {
            display: block;
            margin-bottom: .4rem;
            font-weight: 700;
            color: #45516b;
        }

        .complete-address input {
            box-sizing: border-box;
            width: 100%;
            background: #f5f7fb;
            color: #172033;
        }

        .complete-address__control {
            display: flex;
            gap: .75rem;
        }

        .complete-address__control input {
            flex: 1;
        }

        [hidden] {
            display: none;
        }

        @media (max-width: 680px) {
            form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            form button {
                grid-column: span 2;
            }
        }

        @media (max-width: 480px) {
            form {
                grid-template-columns: 1fr;
            }

            form button {
                grid-column: auto;
            }

            .complete-address__control {
                flex-direction: column;
            }

            dl {
                grid-template-columns: 1fr;
                gap: .15rem;
            }

            dd {
                margin-bottom: .7rem;
            }
        }
    </style>
</head>

<body>
    <main>
        <section class="card" aria-labelledby="page-title">
            <h1 id="page-title">Consulta de CEP</h1>
            <p>Informe o CEP e o número para encontrar o endereço completo.</p>
            <form id="cep-form" novalidate>
                <label class="sr-only" for="cep">CEP</label>
                <input id="cep" name="cep" inputmode="numeric" autocomplete="postal-code" maxlength="9" placeholder="00000-000" required>
                <label class="sr-only" for="numero">Número</label>
                <input id="numero" name="numero" autocomplete="address-line2" maxlength="20" placeholder="Número" required>
                <label class="sr-only" for="complemento">Complemento</label>
                <input id="complemento" name="complemento" autocomplete="address-line3" maxlength="60" placeholder="Complemento (opcional)">
                <button type="submit">Consultar</button>
            </form>
            <p id="message" role="status" aria-live="polite"></p>
            <dl id="result" hidden></dl>
            <div id="complete-address" class="complete-address" hidden>
                <label for="endereco-completo">Endereço completo</label>
                <div class="complete-address__control">
                    <input id="endereco-completo" readonly aria-label="Endereço completo">
                    <button id="copy-address" type="button" disabled>Copiar endereço</button>
                </div>
            </div>
        </section>
    </main>
    <script>
        const form = document.querySelector('#cep-form');
        const input = document.querySelector('#cep');
        const number = document.querySelector('#numero');
        const complement = document.querySelector('#complemento');
        const button = form.querySelector('button');
        const message = document.querySelector('#message');
        const result = document.querySelector('#result');
        const completeAddress = document.querySelector('#complete-address');
        const completeAddressInput = document.querySelector('#endereco-completo');
        const copyAddressButton = document.querySelector('#copy-address');
        let copyButtonResetTimer;

        function resetCopyButton() {
            clearTimeout(copyButtonResetTimer);
            copyAddressButton.textContent = 'Copiar endereço';
            copyAddressButton.classList.remove('is-copied');
        }

        function showCopiedState() {
            resetCopyButton();
            copyAddressButton.textContent = 'Copiado!';
            copyAddressButton.classList.add('is-copied');
            copyButtonResetTimer = setTimeout(resetCopyButton, 2000);
        }

        input.addEventListener('input', () => {
            const digits = input.value.replace(/\D/g, '').slice(0, 8);
            input.value = digits.length > 5 ? `${digits.slice(0, 5)}-${digits.slice(5)}` : digits;
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const cep = input.value.replace(/\D/g, '');
            const numberValue = number.value.trim();
            const complementValue = complement.value.trim();
            result.hidden = true;
            completeAddress.hidden = true;
            copyAddressButton.disabled = true;
            resetCopyButton();
            message.className = '';

            if (cep.length !== 8) {
                message.textContent = 'Informe um CEP com 8 dígitos.';
                message.className = 'error';
                return;
            }

            if (!numberValue) {
                message.textContent = 'Informe o número do endereço.';
                message.className = 'error';
                return;
            }

            button.disabled = true;
            message.textContent = 'Consultando...';

            try {
                const parameters = new URLSearchParams({ numero: numberValue, complemento: complementValue });
                const response = await fetch(`/api/cep/${cep}?${parameters}`, {
                    headers: {
                        Accept: 'application/json'
                    }
                });
                const payload = await response.json();

                if (!response.ok) throw new Error(payload.message || 'Não foi possível consultar o CEP.');

                const labels = {
                    cep: 'CEP',
                    logradouro: 'Logradouro',
                    numero: 'Número',
                    complemento: 'Complemento',
                    bairro: 'Bairro',
                    cidade: 'Cidade',
                    uf: 'UF',
                    ddd: 'DDD'
                };
                result.replaceChildren(...Object.entries(payload.data).filter(([key]) => key !== 'endereco_completo').map(([key, value]) => {
                    const term = document.createElement('dt');
                    const description = document.createElement('dd');
                    term.textContent = labels[key];
                    description.textContent = value || 'Não informado';
                    return [term, description];
                }).flat());
                result.hidden = false;
                completeAddressInput.value = payload.data.endereco_completo;
                copyAddressButton.disabled = !payload.data.endereco_completo;
                completeAddress.hidden = false;
                message.textContent = 'Endereço encontrado.';
            } catch (error) {
                message.textContent = error.message;
                message.className = 'error';
            } finally {
                button.disabled = false;
            }
        });

        copyAddressButton.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(completeAddressInput.value);
                message.textContent = 'Endereço copiado.';
                showCopiedState();
            } catch (error) {
                completeAddressInput.select();

                if (document.execCommand('copy')) {
                    message.textContent = 'Endereço copiado.';
                    showCopiedState();
                    return;
                }

                message.textContent = 'Não foi possível copiar o endereço.';
                message.className = 'error';
            }
        });
    </script>
</body>

</html>
