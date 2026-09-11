<?php

namespace App\Actions\Tenant\CursoTutelado;

use App\Models\Central\Curso;
use App\Models\Tenant\Classe;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\NivelEnsino;
use App\Services\Tenant\Tutela\TutelaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateCursoTutelado
{
    public function __construct(private readonly TutelaService $tutelaService)
    {
    }

    /**
     * @param  array{curso_id: string, nivel_ensino_id: string, classe_ids: array<int, string>, tenant_tutor_id?: string|null}  $validated
     */
    public function handle(Instituicao $instituicao, array $validated): CursoTutelado
    {
        $tenantTutorId = $validated['tenant_tutor_id'] ?? null;
        $instituicaoTutora = $tenantTutorId
            ? $this->tutelaService->validarTutelaExterna($instituicao, $tenantTutorId)
            : null;

        $curso = Curso::query()
            ->whereKey($validated['curso_id'])
            ->where('status', 1)
            ->firstOrFail();

        // Busca e sincroniza dados do tutor ANTES da transaction,
        // enquanto ainda estamos no contexto do tenant atual
        [$classeIds, $nivelEnsinoId] = $tenantTutorId
            ? $this->sincronizarDadosDoTutor($instituicaoTutora, $validated)
            : [$validated['classes'], $validated['nivel_ensino_id']]; // ← era classe_ids

        $cursoTutelado = DB::connection('tenant')->transaction(function () use ($instituicao, $validated, $tenantTutorId, $curso, $instituicaoTutora, $classeIds, $nivelEnsinoId): CursoTutelado {
            if (
                $instituicaoTutora && !$instituicaoTutora->tenant->run(
                    fn(): bool => InstituicaoCurso::query()
                        ->where('instituicao_id', $instituicaoTutora->instituicao->getKey())
                        ->where('curso_id', $curso->getKey())
                        ->exists()
                )
            ) {
                throw ValidationException::withMessages([
                    'curso_id' => 'O instituto tutor não oferece o curso seleccionado.',
                ]);
            }

            $instituicao->setConnection('tenant');

            if (
                Instituicao::query()
                    ->findOrFail($instituicao->getKey())
                    ->instituicaoCursos()
                    ->where('curso_id', $curso->getKey())
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'curso_id' => 'Esta instituição já tem este curso associado.',
                ]);
            }

            $instituicaoCurso = $instituicao->instituicaoCursos()->create([
                'curso_id' => $curso->getKey(),
                'duracao_anos' => $curso->duracao_anos,
            ]);

            $cursoTutelado = $instituicaoCurso->cursoTutelado()->create([
                'instituicao_tutora_id' => $tenantTutorId ? null : $instituicao->getKey(),
                'tipo_tutela' => $tenantTutorId ? 'externa' : 'propria',
            ]);

            $now = now();

            CursoClasse::insert(
                collect($classeIds)->map(fn(string $classeId): array => [
                    'id' => (string) Str::uuid7(),
                    'curso_tutelado_id' => $cursoTutelado->getKey(),
                    'classe_id' => $classeId,
                    'nivel_ensino_id' => $nivelEnsinoId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );

            return $cursoTutelado;
        });

        if ($tenantTutorId) {
            $this->tutelaService->publicarEAssociarCurso($cursoTutelado, $instituicaoTutora);
        }

        return $cursoTutelado->refresh();
    }

    /**
     * Busca classes e nível de ensino no tenant tutor e cria-os localmente se não existirem.
     * Corre FORA da transaction principal para evitar conflitos de contexto de tenant.
     *
     * @param  array{classe_ids: array<int,string>, nivel_ensino_id: string}  $validated
     * @return array{0: array<int,string>, 1: string}
     */
    private function sincronizarDadosDoTutor(mixed $instituicaoTutora, array $validated): array
    {
        $dadosTutor = $instituicaoTutora->tenant->run(function () use ($validated): array {
            return [
                'classes' => Classe::query()
                    ->whereIn('id', $validated['classes']) // ← era classe_ids
                    ->get(['id', 'nome', 'nivel_ensino']),
                'nivelEnsino' => NivelEnsino::query()
                    ->find($validated['nivel_ensino_id'], ['id', 'nome']),
            ];
        });

        // Nível de ensino — busca pelo nome localmente (IDs podem divergir entre tenants)
        $nivelEnsinoId = $validated['nivel_ensino_id']; // fallback
        if ($dadosTutor['nivelEnsino']) {
            $nivelLocal = NivelEnsino::query()->firstOrCreate(
                ['nome' => $dadosTutor['nivelEnsino']->nome],
            );
            $nivelEnsinoId = (string) $nivelLocal->getKey();
        }

        // Classes — busca pelo nome localmente
        $classeIds = [];
        foreach ($dadosTutor['classes'] as $classe) {
            $classeLocal = Classe::query()->firstOrCreate(
                ['nome' => $classe->nome],
                ['nivel_ensino' => $classe->nivel_ensino],
            );
            $classeIds[] = (string) $classeLocal->getKey();
        }

        return [$classeIds, $nivelEnsinoId];
    }
}