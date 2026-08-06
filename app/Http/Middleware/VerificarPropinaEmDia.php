<?php

namespace App\Http\Middleware;

use App\Notifications\PropinaEmAtrasoNotification;
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

        // LOG 6.1: Notificação (cria apenas se o estado da dívida mudou)
        $this->notificarSeNecessario($user, $pendencias);

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
            'meses' => self::MESES,
        ])->toResponse(request())->setStatusCode(403);
    }

private function notificarSeNecessario($user, array $pendencias): void
{
    $totalPendencias = count($pendencias);
    $valorTotal = (float) collect($pendencias)->sum('valor');
    $assinatura = md5($totalPendencias . '-' . $valorTotal);

    Log::debug('[VerificarPropinaEmDia] VERIFICANDO SE PRECISA NOTIFICAR', [
        'user_id' => $user->id,
        'total_pendencias' => $totalPendencias,
        'valor_total' => $valorTotal,
        'assinatura' => $assinatura,
    ]);

    // Compara com a ÚLTIMA notificação deste tipo, lida ou não —
    // o que importa é se o estado da dívida já foi notificado antes,
    // não se a pessoa já a leu.
    $ultima = $user->notifications()
        ->where('type', PropinaEmAtrasoNotification::class)
        ->latest()
        ->first();

    if ($ultima && ($ultima->data['assinatura'] ?? null) === $assinatura) {
        Log::debug('[VerificarPropinaEmDia] notificação já existe para este estado — não duplica', [
            'user_id' => $user->id,
            'assinatura' => $assinatura,
            'notificacao_existente_id' => $ultima->id,
            'ja_lida' => $ultima->read_at !== null,
        ]);
        return;
    }

    $meses = collect($pendencias)
        ->filter(fn ($p) => $p['mes'] !== null)
        ->map(fn ($p) => self::MESES[$p['mes']] . '/' . $p['ano'])
        ->values()
        ->all();

    $user->notify(new PropinaEmAtrasoNotification($totalPendencias, $valorTotal, $meses, $assinatura));

    Log::info('[VerificarPropinaEmDia] notificação criada', [
        'user_id' => $user->id,
        'total_pendencias' => $totalPendencias,
        'valor_total' => $valorTotal,
        'assinatura' => $assinatura,
    ]);
}
}