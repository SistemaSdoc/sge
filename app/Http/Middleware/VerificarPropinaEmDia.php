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
    private const MESES = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    public function __construct(
        private readonly VerificadorPropinaService $verificador
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        Log::debug('[VerificarPropinaEmDia] INÍCIO', [
            'rota' => $request->path(),
            'metodo' => $request->method(),
        ]);

        $user = $request->user();
        $aluno = $user?->aluno;

        if (! $aluno) {
            return $next($request);
        }

        $instituicao = $aluno->user->instituicao;

        if (! $instituicao || $instituicao->tipo !== 'colegio') {
            return $next($request);
        }

        $pendencias = $this->verificador->pendenciasDoAluno($aluno);

        Log::debug('[VerificarPropinaEmDia] RESULTADO DA VERIFICAÇÃO', [
            'aluno_id' => $aluno->id,
            'total_pendencias' => count($pendencias),
        ]);

        if (empty($pendencias)) {
            return $next($request);
        }

        Log::warning('[VerificarPropinaEmDia] ALUNO BLOQUEADO — propinas em atraso', [
            'aluno_id' => $aluno->id,
            'rota' => $request->path(),
            'total_pendencias' => count($pendencias),
        ]);

        $this->verificador->notificarSeNecessario($user, $pendencias);

        return Inertia::render('propinas/bloqueio', [
            'pendencias' => $pendencias,
            'total' => count($pendencias),
            'previousUrl' => url()->previous(),
            'meses' => self::MESES,
        ])->toResponse($request)->setStatusCode(403);
    }
}
