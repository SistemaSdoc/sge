<?php

namespace App\Services\Central;

use App\Enums\TenantStatus;
use App\Models\Central\DeclaracaoEstagio;
use App\Models\Central\HistoricoDeclaracaoEstagio;
use App\Models\Central\Tenant;
use App\Models\Tenant\Aluno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class DeclaracaoEstagioService
{
    private const TIPOS_INSTITUICAO = ['colegio', 'instituto'];

    /** @return array<int, array{aluno:string, matricula:?string, emitidoEm:string}> */
    public function listarHistorico(): array
    {
        return HistoricoDeclaracaoEstagio::query()
            ->orderByDesc('emitido_em')
            ->get(['aluno_nome', 'matricula', 'emitido_em'])
            ->map(fn (HistoricoDeclaracaoEstagio $item): array => [
                'aluno' => $item->aluno_nome,
                'matricula' => $item->matricula,
                'emitidoEm' => $item->emitido_em->format('d/m/Y H:i'),
            ])
            ->all();
    }

    public function registrarHistorico(DeclaracaoEstagio $declaracao): HistoricoDeclaracaoEstagio
    {
        return HistoricoDeclaracaoEstagio::create([
            'tenant_id' => $declaracao->tenant_id,
            'aluno_id' => $declaracao->aluno_id,
            'aluno_nome' => $declaracao->aluno_nome,
            'matricula' => $declaracao->matricula,
            'gerado_por_id' => $declaracao->gerado_por_id,
            'emitido_em' => $declaracao->emitido_em,
        ]);
    }

    /** @return array<int, array{id: string, nome: string, tipo: string}> */
    public function listarInstituicoes(): array
    {
        return Tenant::query()
            ->whereIn('status', [TenantStatus::ACTIVE->value, TenantStatus::TRIAL->value])
            ->whereIn('instituicao_tipo', self::TIPOS_INSTITUICAO)
            ->whereNotNull('instituicao_nome')
            ->orderBy('instituicao_nome')
            ->get(['id', 'instituicao_nome', 'instituicao_tipo'])
            ->map(fn (Tenant $tenant): array => [
                'id' => (string) $tenant->getTenantKey(),
                'nome' => $tenant->instituicao_nome,
                'tipo' => $tenant->instituicao_tipo,
            ])
            ->values()
            ->all();
    }

    public function pesquisarAlunos(Tenant $tenant, ?string $pesquisa, int $pagina): LengthAwarePaginator
    {
        $instituicaoId = $tenant->instituicao_id;

        if (! $instituicaoId) {
            return new LengthAwarePaginator([], 0, 10, $pagina);
        }

        $pesquisa = trim((string) $pesquisa);

        return $tenant->run(function () use ($instituicaoId, $pesquisa, $pagina): LengthAwarePaginator {
            $query = Aluno::query()
                ->select('alunos.*')
                ->join('inscricoes as inscricoes_aluno', 'inscricoes_aluno.id', '=', 'alunos.inscricao_id')
                ->join('candidatos as candidatos_aluno', 'candidatos_aluno.id', '=', 'inscricoes_aluno.candidato_id')
                ->where('alunos.instituicao_id', $instituicaoId)
                ->whereNull('candidatos_aluno.deleted_at')
                ->whereHas(
                    'elementosGrupoPap.grupoPap.turma.cursoClasseTurno.cursoClasse.classe',
                    fn (Builder $classeQuery) => $classeQuery->where('nome', '13ª'),
                )
                ->when($pesquisa !== '', function (Builder $alunoQuery) use ($pesquisa): void {
                    $alunoQuery->where(function (Builder $pesquisaQuery) use ($pesquisa): void {
                        $pesquisaQuery->where('candidatos_aluno.nome', 'like', "%{$pesquisa}%")
                            ->orWhere('alunos.matricula', 'like', "%{$pesquisa}%")
                            ->orWhere('alunos.numero_processo', 'like', "%{$pesquisa}%");
                    });
                })
                ->with([
                    'inscricao.candidato:id,nome',
                    'user:id,avatar',
                ])
                ->orderBy('candidatos_aluno.nome');

            return $query->paginate(10, ['alunos.*'], 'page', $pagina)
                ->through(fn (Aluno $aluno): array => [
                    'id' => (string) $aluno->getKey(),
                    'nome' => $aluno->inscricao?->candidato?->nome ?? 'Nome indisponível',
                    'matricula' => $aluno->matricula,
                    'numero_processo' => $aluno->numero_processo,
                    'avatar' => $aluno->user?->avatar,
                ]);
        });
    }

    public function obterInstituicaoElegivel(string $tenantId): Tenant
    {
        return Tenant::query()
            ->whereKey($tenantId)
            ->whereIn('status', [TenantStatus::ACTIVE->value, TenantStatus::TRIAL->value])
            ->whereIn('instituicao_tipo', self::TIPOS_INSTITUICAO)
            ->firstOrFail();
    }

    /**
     * @param array{
     *     nota: numeric-string|int|float,
     *     responsavel_nome: string,
     *     responsavel_cargo: string
     * } $dadosEstagio
     */
    public function emitir(Tenant $tenant, string $alunoId, string $utilizadorId, array $dadosEstagio): DeclaracaoEstagio
    {
        $dadosAluno = $tenant->run(function () use ($tenant, $alunoId): array {
            $aluno = Aluno::query()
                ->where('instituicao_id', $tenant->instituicao_id)
                ->whereHas('inscricao.candidato')
                ->whereHas(
                    'elementosGrupoPap.grupoPap.turma.cursoClasseTurno.cursoClasse.classe',
                    fn (Builder $classeQuery) => $classeQuery->where('nome', '13ª'),
                )
                ->with([
                    'inscricao.candidato:id,nome,bi',
                    'inscricao.anoLectivo:id,nome',
                    'inscricao.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso:id,nome',
                ])
                ->find($alunoId);

            if (! $aluno || blank($aluno->inscricao?->candidato?->nome)) {
                throw ValidationException::withMessages([
                    'aluno_id' => 'O aluno não pertence à instituição seleccionada ou não tem nome registado.',
                ]);
            }

            return [
                'id' => (string) $aluno->getKey(),
                'nome' => $aluno->inscricao->candidato->nome,
                'bi' => $aluno->inscricao->candidato->bi,
                'curso' => $aluno->inscricao?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                'ano' => $aluno->inscricao?->anoLectivo?->nome,
                'matricula' => $aluno->matricula,
                'numero_processo' => $aluno->numero_processo,
            ];
        });

        $declaracao = new DeclaracaoEstagio([
            'tenant_id' => $tenant->getTenantKey(),
            'instituicao_nome' => $tenant->instituicao_nome,
            'instituicao_tipo' => $tenant->instituicao_tipo,
            'aluno_id' => $dadosAluno['id'],
            'aluno_nome' => $dadosAluno['nome'],
            'matricula' => $dadosAluno['matricula'],
            'numero_processo' => $dadosAluno['numero_processo'],
            'bi' => $dadosAluno['bi'],
            'curso' => $dadosAluno['curso'],
            'ano' => $dadosAluno['ano'],
            'nota' => $dadosEstagio['nota'],
            'responsavel_nome' => $dadosEstagio['responsavel_nome'],
            'responsavel_cargo' => $dadosEstagio['responsavel_cargo'],
            'empresa_nome' => 'Tunganetu',
            'emitido_em' => now(),
        ]);
        $declaracao->gerado_por_id = $utilizadorId;
        $declaracao->avaliacao = $declaracao->desempenho;

        $payload = [
            $declaracao->aluno_nome,
            $declaracao->instituicao_nome,
            $declaracao->nota,
            $declaracao->avaliacao,
            $declaracao->emitido_em->format('Y-m-d'),
        ];
        $declaracao->public_token = rtrim(
            strtr(Crypt::encryptString(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), '+/', '-_'),
            '=',
        );

        return $declaracao;
    }
}
