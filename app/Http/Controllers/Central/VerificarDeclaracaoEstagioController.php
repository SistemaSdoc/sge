<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\VerificacaoDeclaracaoEstagio;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use JsonException;

class VerificarDeclaracaoEstagioController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $token): Response
    {
        $verificadoEm = now();

        $tokenValido = false;

        try {
            $payload = json_decode($this->decryptToken($token), true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($payload)) {
                throw new InvalidArgumentException('O conteúdo do código QR não é válido.');
            }

            if (array_is_list($payload)) {
                if (count($payload) !== 5) {
                    throw new InvalidArgumentException('O conteúdo do código QR não é válido.');
                }

                [$alunoNome, $instituicaoNome, $nota, $avaliacao, $emitidoEm] = $payload;
                $empresaNome = 'Tunganetu';
            } else {
                $alunoNome = $payload['aluno_nome'] ?? null;
                $instituicaoNome = $payload['instituicao_nome'] ?? null;
                $nota = $payload['nota'] ?? null;
                $avaliacao = $payload['avaliacao'] ?? null;
                $empresaNome = $payload['empresa_nome'] ?? null;
                $emitidoEm = $payload['emitido_em'] ?? null;
            }

            if (
                ! is_string($alunoNome)
                || ! is_string($instituicaoNome)
                || ! is_numeric($nota)
                || ! is_string($avaliacao)
                || ! is_string($empresaNome)
                || ! is_string($emitidoEm)
            ) {
                throw new InvalidArgumentException('O conteúdo do código QR não é válido.');
            }

            $declaracao = [
                'aluno_nome' => $alunoNome,
                'instituicao_nome' => $instituicaoNome,
                'nota' => $nota,
                'avaliacao' => $avaliacao,
                'empresa_nome' => $empresaNome,
                'emitido_em' => Carbon::parse($emitidoEm)->format('d/m/Y'),
                'duracao' => '3 (três) meses',
                'carga_horaria' => '120 horas',
                'estagio_realizado' => true,
            ];
            $tokenValido = true;
        } catch (DecryptException|JsonException|InvalidArgumentException) {
            $declaracao = null;
        }

        VerificacaoDeclaracaoEstagio::create([
            'token_hash' => hash('sha256', $token),
            'ip_origem' => $request->ip(),
            'valida' => $tokenValido,
            'verificado_em' => $verificadoEm,
        ]);

        return Inertia::render('central/declaracoes-estagio/verificar', [
            'declaracao' => $declaracao,
            'verificadoEm' => $verificadoEm->format('d/m/Y').' às '.$verificadoEm->format('H:i'),
            'empresaSiteUrl' => config('app.empresa_site_url'),
            'empresa' => [
                'nome' => 'Tunganetu',
                'nif' => '5003407764',
                'morada' => 'Luanda, Maculusso, Rua Luther King, Edifício dos Estudantes, 3º Andar, Apt. nº 12',
                'telefones' => [
                    ['texto' => '+244 923 678 529', 'link' => '+244923678529'],
                    ['texto' => '+244 927 800 505', 'link' => '+244927800505'],
                ],
                'email' => 'tunganetu@gmail.com',
                'logo' => asset('images/tunganetu-02.png'),
                'iniciais' => 'T',
            ],
        ]);
    }

    private function decryptToken(string $token): string
    {
        $encodedToken = strtr($token, '-_', '+/');
        $encodedToken .= str_repeat('=', (4 - strlen($encodedToken) % 4) % 4);

        try {
            $decryptedPayload = Crypt::decryptString($encodedToken);
            json_decode($decryptedPayload, true, 512, JSON_THROW_ON_ERROR);

            return $decryptedPayload;
        } catch (DecryptException|JsonException $exception) {
            $legacyToken = base64_decode($encodedToken, true);

            if ($legacyToken === false) {
                throw $exception;
            }

            return Crypt::decryptString($legacyToken);
        }
    }
}
