<?php

namespace App\Http\Middleware;

use App\Services\VerificadorPropinaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerificarPropinaEmDia
{
    public function __construct(
        private readonly VerificadorPropinaService $verificador
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $aluno = $user?->aluno;

        Log::debug('[VerificarPropinaEmDia] handle', [
            'user_id' => $user?->id,
            'tem_aluno' => (bool) $aluno,
            'aluno_id' => $aluno?->id,
            'rota' => $request->path(),
        ]);

        if (! $aluno) {
            Log::debug('[VerificarPropinaEmDia] sem aluno associado — deixa passar');
            return $next($request);
        }

        // O service devolve directamente a lista de pendências (array simples).
        $pendencias = $this->verificador->pendenciasDoAluno($aluno);

        Log::debug('[VerificarPropinaEmDia] resultado da verificação', [
            'aluno_id' => $aluno->id,
            'total_pendencias' => count($pendencias),
            'pendencias' => $pendencias,
        ]);

        if (empty($pendencias)) {
            Log::debug('[VerificarPropinaEmDia] aluno em dia — deixa passar', ['aluno_id' => $aluno->id]);
            return $next($request);
        }

        Log::warning('[VerificarPropinaEmDia] aluno bloqueado por propinas em atraso', [
            'aluno_id' => $aluno->id,
            'rota' => $request->path(),
            'total_pendencias' => count($pendencias),
        ]);

        $meses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        return response()->view('errors.propina-pendente', [
            'pendencias' => $pendencias,
            'total' => count($pendencias),
            'previousUrl' => url()->previous(),
            'meses' => $meses,
        ], 403);
    }
}