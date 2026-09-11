<?php

namespace App\Actions\Tenant\CursoTutelado;

use App\Models\Central\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Services\Tenant\Tutela\TutelaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Cria um curso tutelado e as suas classes no tenant actual.
 */
class CreateCursoTutelado
{
    public function __construct(private readonly TutelaService $tutelaService) {}

    /**
     * Associa um curso central à instituição e publica a tutela externa,
     * quando aplicável.
     *
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

        $cursoTutelado = DB::connection('tenant')->transaction(function () use ($instituicao, $validated, $tenantTutorId, $curso, $instituicaoTutora): CursoTutelado {
            if ($instituicaoTutora && ! $instituicaoTutora->tenant->run(
                fn (): bool => InstituicaoCurso::query()
                    ->where('instituicao_id', $instituicaoTutora->instituicao->getKey())
                    ->where('curso_id', $curso->getKey())
                    ->exists()
            )) {
                throw ValidationException::withMessages([
                    'curso_id' => 'O instituto tutor não oferece o curso seleccionado.',
                ]);
            }

            $instituicao->setConnection('tenant');

            if (Instituicao::query()
                ->findOrFail($instituicao->getKey())
                ->instituicaoCursos()
                ->where('curso_id', $curso->getKey())
                ->exists()) {
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
                collect($validated['classe_ids'])->map(fn (string $classeId): array => [
                    'id' => (string) Str::uuid7(),
                    'curso_tutelado_id' => $cursoTutelado->getKey(),
                    'classe_id' => $classeId,
                    'nivel_ensino_id' => $validated['nivel_ensino_id'],
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
}
