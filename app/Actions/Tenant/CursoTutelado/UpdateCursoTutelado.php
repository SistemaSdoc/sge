<?php

namespace App\Actions\Tenant\CursoTutelado;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Services\Tenant\Tutela\TutelaService;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza a configuração e a tutela de um curso.
 */
class UpdateCursoTutelado
{
    public function __construct(private readonly TutelaService $tutelaService) {}

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
            $houveMudancaDeTutor = $tenantTutorId !== null && $tenantTutorId !== $tutorAtualId;

            if ($houveMudancaDeTutor) {
                $haTutelaExternaAtual = $cursoTutelado->tipo_tutela === 'externa' && $tutorAtualId !== null;

                if ($haTutelaExternaAtual) {
                    $sharedAnteriorId = CursoTuteladoShared::on(
                        config('tenancy.database.central_connection', config('database.default'))
                    )
                        ->where('curso_tutelado_tutelado_id', $cursoTutelado->getKey())
                        ->where('tenant_tutor_id', $tutorAtualId)
                        ->where('status', TutelaStatus::ACTIVO)
                        ->latest('updated_at')
                        ->value('id');
                    $instituicaoTutora = $this->tutelaService->validarTutelaExterna($instituicao, $tenantTutorId);
                    $sharedProposto = $this->tutelaService->publicarSemAssociarCurso($cursoTutelado, $instituicaoTutora);
                    $this->tutelaService->notificarTrocaTutela($cursoTutelado->fresh(), $tutorAtualId, $sharedAnteriorId, $sharedProposto);
                    $this->tutelaService->notificarTrocaPendente(
                        $sharedProposto,
                        $tutorAtualId,
                    );

                    return;
                }

                $instituicaoTutora = $this->tutelaService->validarTutelaExterna($instituicao, $tenantTutorId);
                $this->tutelaService->publicarEAssociarCurso($cursoTutelado, $instituicaoTutora);
            } elseif ($tenantTutorId === null && $cursoTutelado->tipo_tutela === 'externa') {
                $sharedActualId = CursoTuteladoShared::on(
                    config('tenancy.database.central_connection', config('database.default'))
                )
                    ->where('curso_tutelado_tutelado_id', $cursoTutelado->getKey())
                    ->where('tenant_tutor_id', $tutorAtualId)
                    ->where('status', TutelaStatus::ACTIVO)
                    ->latest('updated_at')
                    ->value('id');
                $this->tutelaService->notificarConversaoTutelaPropria(
                    $cursoTutelado,
                    (string) $tutorAtualId,
                    (string) $sharedActualId,
                );
                $this->tutelaService->notificarResultadoConversaoTutelaPropria(
                    CursoTuteladoShared::on(
                        config('tenancy.database.central_connection', config('database.default'))
                    )->findOrFail($sharedActualId),
                    (string) $tutorAtualId,
                    'pendente',
                );

                return;
            }

            $curso = $cursoTutelado->instituicaoCurso->curso;

            $curso->update([
                'nome' => $validated['nome'],
                'duracao_anos' => $validated['duracao_anos'],
            ]);

            $cursoTutelado->instituicaoCurso()->update([
                'duracao_anos' => $validated['duracao_anos'],
            ]);

            $cursoTutelado->classes()->sync(
                collect($validated['classes'])
                    ->mapWithKeys(fn (string $classeId): array => [
                        $classeId => [
                            'nivel_ensino_id' => $validated['nivel_ensino_id'],
                        ],
                    ])
                    ->all()
            );
        });
    }
}
