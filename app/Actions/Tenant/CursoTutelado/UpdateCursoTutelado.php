<?php

namespace App\Actions\Tenant\CursoTutelado;

use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Services\Tenant\CursoTuteladoSharedService;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza a configuração e a tutela de um curso.
 */
class UpdateCursoTutelado
{
    public function __construct(private readonly CursoTuteladoSharedService $sharedService) {}

    /**
     * Aplica a nova duração, classes e instituição tutora.
     *
     * @param  array{tenant_tutor_id?: string|null, duracao_anos: int, classes: array<int, string>}  $validated
     */
    public function handle(Instituicao $instituicao, CursoTutelado $cursoTutelado, array $validated): void
    {
        DB::transaction(function () use ($instituicao, $cursoTutelado, $validated): void {
            $tenantTutorId = $validated['tenant_tutor_id'] ?? null;

            if ($tenantTutorId) {
                $tenantTutorNome = $this->sharedService->validarTutelaExterna($instituicao, $tenantTutorId);
                
                $this->sharedService->publicarEAssociar($cursoTutelado, $tenantTutorId, $tenantTutorNome);
            } else {
                $this->sharedService->tornarPropria($cursoTutelado, $instituicao->getKey());
            }

            $cursoTutelado->instituicaoCurso()->update([
                'duracao_anos' => $validated['duracao_anos'],
            ]);

            $cursoTutelado->classes()->sync($validated['classes']);
        });
    }
}
