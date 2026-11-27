<?php

namespace App\Services\Tenant;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Tenant\User;
use App\Notifications\PropinaEmAtrasoNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class NotificacaoService
{
    public function __construct(
        private readonly VerificadorPropinaService $verificador,
    ) {}

    public function pagina(User $user): LengthAwarePaginator
    {
        $this->limparNotificacoesResolvidas($user);

        return $user->notifications()
            ->latest()
            ->paginate(20);
    }

    /** @return Collection<int, DatabaseNotification> */
    public function sino(User $user): Collection
    {
        $this->limparNotificacoesResolvidas($user);

        return $user->notifications()
            ->latest()
            ->limit(20)
            ->get();
    }

    public function naoLidas(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function formatarNotificacao(DatabaseNotification $notificacao): array
    {
        $data = $notificacao->data;

        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }

        if (! is_array($data)) {
            $data = [];
        }

        $tipo = $data['tipo'] ?? 'geral';

        $base = [
            'id' => $notificacao->id,
            'tipo' => $tipo,
            'titulo' => $data['titulo'] ?? 'Notificação',
            'mensagem' => $data['mensagem'] ?? '',
            'lida' => $notificacao->read_at !== null,
            'criada_em' => $notificacao->created_at
                ? $notificacao->created_at->diffForHumans()
                : '',
            'url' => $data['url'] ?? null,
        ];

        return array_merge($base, $this->detalhesPorTipo($tipo, $data));
    }

    public function serializar(DatabaseNotification $notification, bool $detalhada = false): array
    {
        $data = $notification->data;
        $tipo = $data['tipo'] ?? null;
        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        if (in_array($tipo, ['solicitacao_tutela', 'troca_tutela', 'conversao_tutela_propria'], true) && empty($data['curso_tutelado_shared_id'])) {
            $resolvedSharedId = $this->resolverSharedIdDaNotificacao($notification, $centralConnection);

            if ($resolvedSharedId) {
                $data['curso_tutelado_shared_id'] = $resolvedSharedId;
            }
        }

        if ($tipo === 'solicitacao_tutela' && ! empty($data['curso_tutelado_shared_id'])) {
            $data['status'] = CursoTuteladoShared::on($centralConnection)
                ->whereKey($data['curso_tutelado_shared_id'])
                ->value('status');
        }

        if ($tipo === 'troca_tutela' && ! isset($data['status'])) {
            $data['status'] = ! empty($data['curso_tutelado_shared_id'])
                ? CursoTuteladoShared::on($centralConnection)
                    ->whereKey($data['curso_tutelado_shared_id'])
                    ->value('status')
                : 'pendente_troca';
        }

        if ($tipo === 'conversao_tutela_propria_resultado' && ($data['resultado'] ?? null) === 'pendente') {
            $data['tipo'] = 'conversao_tutela_propria_pendente';
        }

        $data['status'] ??= 'pendente';

        return [
            'id' => $notification->id,
            'tipo' => $data['tipo'] ?? null,
            'titulo' => $data['titulo'] ?? '',
            'mensagem' => $data['mensagem'] ?? '',
            'dados' => $detalhada ? $data : [],
            'lida' => $notification->read_at !== null,
            'criada_em' => $notification->created_at->diffForHumans(),
            'criada_em_iso' => $notification->created_at->toISOString(),
        ];
    }

    public function resolverSharedIdDaNotificacao(
        DatabaseNotification $notification,
        string $centralConnection,
    ): ?string {
        $data = $notification->data;

        return CursoTuteladoShared::on($centralConnection)
            ->where('curso_nome', $data['curso_nome'] ?? '')
            ->where('tenant_tutor_id', (string) tenancy()->tenant->getTenantKey())
            ->latest('updated_at')
            ->value('id');
    }

    private function detalhesPorTipo(string $tipo, array $data): array
    {
        return match ($tipo) {
            'criado' => [
                'titulo' => 'Novo prazo de prova',
                'mensagem' => ($data['titulo'] ?? '').' — '.($data['disciplina'] ?? 'Todas').' ('.($data['classe'] ?? 'Todas').')',
                'prazo_id' => $data['prazo_id'] ?? null,
                'disciplina' => $data['disciplina'] ?? null,
                'classe' => $data['classe'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
                'periodo' => $data['periodo'] ?? null,
            ],
            'prorrogado' => [
                'titulo' => 'Prazo prorrogado',
                'mensagem' => 'Nova data limite: '.($data['data_limite'] ?? ''),
                'prazo_id' => $data['prazo_id'] ?? null,
                'disciplina' => $data['disciplina'] ?? null,
                'classe' => $data['classe'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],
            'a_expirar' => [
                'titulo' => 'Prazo a expirar',
                'mensagem' => 'Faltam menos de 30 minutos para o prazo terminar.',
                'prazo_id' => $data['prazo_id'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],
            'expirado' => [
                'titulo' => 'Prazo expirado',
                'mensagem' => 'O prazo já não aceita submissões.',
                'prazo_id' => $data['prazo_id'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],
            'fechado' => [
                'titulo' => 'Prazo encerrado',
                'mensagem' => 'O prazo foi encerrado manualmente pelo diretor.',
                'prazo_id' => $data['prazo_id'] ?? null,
                'data_limite' => $data['data_limite'] ?? null,
            ],
            'justificativa_avaliada' => [
                'titulo' => ($data['status'] ?? null) === 'aceita'
                    ? 'Justificativa aceite'
                    : 'Justificativa recusada',
                'mensagem' => 'A sua justificativa foi '.($data['status_label'] ?? $data['status'] ?? '').'.',
                'justificativa_id' => $data['justificativa_id'] ?? null,
                'prazo_id' => $data['prazo_id'] ?? null,
                'status' => $data['status'] ?? null,
                'status_label' => $data['status_label'] ?? null,
                'parecer_diretor' => $data['parecer_diretor'] ?? null,
            ],
            'submissao_avaliada' => [
                'titulo' => ($data['estado'] ?? null) === 'aprovado'
                    ? 'Submissão aprovada'
                    : 'Submissão rejeitada',
                'mensagem' => 'A sua submissão para "'.($data['prazo_titulo'] ?? 'prazo').'" foi '.($data['estado_label'] ?? $data['estado'] ?? '').'.',
                'submissao_id' => $data['submissao_id'] ?? null,
                'prazo_id' => $data['prazo_id'] ?? null,
                'estado' => $data['estado'] ?? null,
                'estado_label' => $data['estado_label'] ?? null,
                'parecer_diretor' => $data['parecer_diretor'] ?? null,
                'versao' => $data['versao'] ?? null,
                'turma' => $data['turma'] ?? null,
                'disciplina' => $data['disciplina'] ?? null,
                'classe' => $data['classe'] ?? null,
            ],
            'prazo_expirado' => [
                'titulo' => 'Prazo expirado',
                'mensagem' => ($data['titulo'] ?? '').' — '.($data['submeteram'] ?? 0).'/'.($data['total_professores'] ?? 0).' professores submeteram',
                'prazo_id' => $data['prazo_id'] ?? null,
                'stats' => [
                    'total' => $data['total_professores'] ?? 0,
                    'submeteram' => $data['submeteram'] ?? 0,
                    'nao_submeteram' => $data['nao_submeteram'] ?? 0,
                    'justificaram' => $data['justificaram'] ?? 0,
                ],
            ],
            'justificativa_enviada' => [
                'titulo' => 'Nova justificativa',
                'mensagem' => ($data['professor_nome'] ?? 'Professor').' enviou uma justificativa.',
                'justificativa_id' => $data['justificativa_id'] ?? null,
                'prazo_id' => $data['prazo_id'] ?? null,
                'professor_nome' => $data['professor_nome'] ?? null,
                'motivo' => $data['motivo'] ?? null,
            ],
            'nova_submissao' => [
                'titulo' => 'Nova submissão recebida',
                'mensagem' => ($data['professor_nome'] ?? 'Professor').' submeteu "'.($data['prazo_titulo'] ?? 'prazo').'".',
                'submissao_id' => $data['submissao_id'] ?? null,
                'prazo_id' => $data['prazo_id'] ?? null,
                'professor_nome' => $data['professor_nome'] ?? null,
                'prazo_titulo' => $data['prazo_titulo'] ?? null,
                'disciplina' => $data['disciplina'] ?? null,
                'classe' => $data['classe'] ?? null,
                'turma' => $data['turma'] ?? null,
                'versao' => $data['versao'] ?? null,
            ],
            'propina_atraso' => [
                'titulo' => 'Propina em atraso',
                'mensagem' => 'Tem propinas em atraso. Regularize para continuar a aceder.',
                'meses' => $data['meses'] ?? [],
                'valor_total' => $data['valor_total'] ?? null,
            ],
            'perfil_incompleto' => [
                'titulo' => 'Alerta! Complete o seu perfil',
                'mensagem' => 'O seu acesso está limitado. Preencha os dados obrigatórios para continuar.',
                'url' => '/dashboard/settings/profile',
            ],
            default => [
                'titulo' => $data['titulo'] ?? 'Notificação',
                'mensagem' => $data['mensagem'] ?? '',
            ],
        };
    }

    private function limparNotificacoesResolvidas(User $user): void
    {
        $aluno = $user->aluno;

        if (! $aluno) {
            return;
        }

        $pendenciasAtuais = $this->verificador->pendenciasDoAluno($aluno);
        $assinaturaAtual = md5(count($pendenciasAtuais).'-'.collect($pendenciasAtuais)->sum('valor'));

        $notificacoesPropina = $user->notifications()
            ->where('type', PropinaEmAtrasoNotification::class)
            ->get();

        foreach ($notificacoesPropina as $notificacao) {
            $assinaturaNotificacao = $notificacao->data['assinatura'] ?? null;
            $resolvida = empty($pendenciasAtuais) || $assinaturaNotificacao !== $assinaturaAtual;

            if ($resolvida) {
                Log::debug('[NotificacaoService] a apagar notificação resolvida', [
                    'user_id' => $user->id,
                    'notificacao_id' => $notificacao->id,
                    'assinatura_notificacao' => $assinaturaNotificacao,
                    'assinatura_atual' => $assinaturaAtual,
                ]);

                $notificacao->delete();
            }
        }
    }
}
