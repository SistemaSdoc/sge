<?php

namespace App\Actions\Tenant\CursoTutelado;

use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Services\Tenant\Tutela\TutelaService;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza a configuração e a tutela de um curso.
 */
class UpdateCursoTutelado
{
    public function __construct(private readonly TutelaService $tutelaService)
    {
    }

    /**
     * Aplica a nova duração, classes e instituição tutora.
     *
     * @param  array{tenant_tutor_id?: string|null, duracao_anos: int, classes: array<int, string>}  $validated
     */
    public function handle(Instituicao $instituicao, CursoTutelado $cursoTutelado, array $validated): void
    {
        DB::transaction(function () use ($instituicao, $cursoTutelado, $validated): void {
            $tenantTutorId = $validated['tenant_tutor_id'] ?? null;
            $tutorAtualId = $cursoTutelado->tipo_tutela === 'externa'
                ? $this->tutelaService->tutorAtual($cursoTutelado)
                : null;

            if ($tenantTutorId) {
                if ($tenantTutorId !== $tutorAtualId) {
                    $instituicaoTutora = $this->tutelaService->validarTutelaExterna($instituicao, $tenantTutorId);
                    $this->tutelaService->publicarEAssociarCurso($cursoTutelado, $instituicaoTutora);
                }
            } elseif ($cursoTutelado->tipo_tutela === 'externa') {
                $this->tutelaService->converterParaTutelaPropria($cursoTutelado, $instituicao->getKey());
            }

            $cursoTutelado->instituicaoCurso()->update([
                'duracao_anos' => $validated['duracao_anos'],
            ]);

            $cursoTutelado->classes()->sync($validated['classes']);
        });
    }
}
