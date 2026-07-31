<?php

namespace App\Http\Middleware;

use App\Services\VerificadorPropinaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class VerificarPropinaEmDia
{
    public function __construct(
        private readonly VerificadorPropinaService $verificador
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // LOG 1: Início da requisição
        Log::debug('[VerificarPropinaEmDia] INÍCIO', [
            'rota' => $request->path(),
            'metodo' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $user = $request->user();
        $aluno = $user?->aluno;

        // LOG 2: Dados do usuário
        Log::debug('[VerificarPropinaEmDia] USUÁRIO', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'tem_aluno' => (bool) $aluno,
            'aluno_id' => $aluno?->id,
            'aluno_nome' => $aluno?->nome ?? 'N/A',
        ]);

        // Se não tem aluno, libera
        if (! $aluno) {
            Log::debug('[VerificarPropinaEmDia] SEM ALUNO ASSOCIADO — libera acesso');
            return $next($request);
        }

        // LOG 3: Dados da instituição
        $instituicao = $aluno->user->instituicao;
        Log::debug('[VerificarPropinaEmDia] INSTITUIÇÃO', [
            'aluno_id' => $aluno->id,
            'instituicao_id' => $instituicao?->id,
            'instituicao_nome' => $instituicao?->nome,
            'tipo' => $instituicao?->tipo,
            'eh_colegio' => ($instituicao && $instituicao->tipo === 'colegio'),
        ]);

        // Verifica o tipo da instituição
        if (! $instituicao || $instituicao->tipo !== 'colegio') {
            Log::debug('[VerificarPropinaEmDia] INSTITUIÇÃO NÃO É "colegio" — bloqueio desativado', [
                'aluno_id' => $aluno->id,
                'tipo' => $instituicao?->tipo,
            ]);
            return $next($request);
        }

        // LOG 4: Chamada ao serviço de verificação
        Log::debug('[VerificarPropinaEmDia] CHAMANDO VerificadorPropinaService', [
            'aluno_id' => $aluno->id,
        ]);

        // Agora sim, verifica pendências
        $pendencias = $this->verificador->pendenciasDoAluno($aluno);

        // LOG 5: Resultado da verificação
        Log::debug('[VerificarPropinaEmDia] RESULTADO DA VERIFICAÇÃO', [
            'aluno_id' => $aluno->id,
            'total_pendencias' => count($pendencias),
            'pendencias' => $pendencias,
            'tipo_instituicao' => $instituicao->tipo,
        ]);

        if (empty($pendencias)) {
            Log::debug('[VerificarPropinaEmDia] ALUNO EM DIA — libera acesso', [
                'aluno_id' => $aluno->id,
            ]);
            return $next($request);
        }

        // LOG 6: Bloqueio
        Log::warning('[VerificarPropinaEmDia] ALUNO BLOQUEADO — propinas em atraso', [
            'aluno_id' => $aluno->id,
            'rota' => $request->path(),
            'total_pendencias' => count($pendencias),
            'itens_em_atraso' => array_column($pendencias, 'nome'),
        ]);

        $previousUrl = url()->previous();

        // LOG 7: Renderização da página de bloqueio
        Log::debug('[VerificarPropinaEmDia] RENDERIZANDO BLOQUEIO', [
            'aluno_id' => $aluno->id,
            'previous_url' => $previousUrl,
        ]);

        return Inertia::render('propinas/bloqueio', [
            'pendencias' => $pendencias,
            'total' => count($pendencias),
            'previousUrl' => $previousUrl,
            'meses' => [
                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
            ],
        ])->toResponse(request())->setStatusCode(403);
    }
}