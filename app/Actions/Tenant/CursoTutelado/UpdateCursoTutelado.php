<?php

namespace App\Actions\Tenant\CursoTutelado;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Services\Tenant\Tutela\TutelaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Actualiza a configuração e a tutela de um curso.
 */
class UpdateCursoTutelado
{
    public function __construct(private readonly TutelaService $tutelaService)
    {
    }

    /**
     * Aplica as alterações da associação local e da instituição tutora.
     *
     * @param  array{tenant_tutor_id?: string|null, nivel_ensino_id: string, classes: array<int, string>}  $validated
     */
    public function handle(Instituicao $instituicao, CursoTutelado $cursoTutelado, array $validated): void
    {
        DB::transaction(function () use ($instituicao, $cursoTutelado, $validated): void {
            $tenantTutorId = $validated['tenant_tutor_id'] ?? null;
            $tutelaPendente = false;
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

                    $tutelaPendente = true;

                }

                if (!$tutelaPendente) {
                    $instituicaoTutora = $this->tutelaService->validarTutelaExterna($instituicao, $tenantTutorId);
                    $this->tutelaService->publicarEAssociarCurso($cursoTutelado, $instituicaoTutora);
                }
            } elseif ($tenantTutorId === null && $cursoTutelado->tipo_tutela === 'externa') {
                $sharedActual = CursoTuteladoShared::on(
                    config('tenancy.database.central_connection', config('database.default'))
                )
                    ->where('curso_tutelado_tutelado_id', $cursoTutelado->getKey())
                    ->where('tenant_tutor_id', $tutorAtualId)
                    ->where('status', TutelaStatus::ACTIVO)
                    ->latest('updated_at')
                    ->first();

                if ($sharedActual) {
                    $this->tutelaService->notificarConversaoTutelaPropria(
                        $cursoTutelado,
                        (string) $tutorAtualId,
                        (string) $sharedActual->getKey(),
                    );
                    $this->tutelaService->notificarResultadoConversaoTutelaPropria(
                        $sharedActual,
                        (string) $tutorAtualId,
                        'pendente',
                    );
                } else {
                    $sharedAssociado = $cursoTutelado->curso_tutelado_shared_id
                        ? CursoTuteladoShared::on(
                            config('tenancy.database.central_connection', config('database.default'))
                        )->find($cursoTutelado->curso_tutelado_shared_id)
                        : null;

                    if (
                        $sharedAssociado && !in_array($sharedAssociado->status, [
                            TutelaStatus::REJEITADO,
                            TutelaStatus::ENCERRADO,
                        ], true)
                    ) {
                        throw ValidationException::withMessages([
                            'tenant_tutor_id' => 'A solicitação de tutela ainda aguarda decisão do instituto tutor.',
                        ]);
                    }

                    $this->tutelaService->encerrarVinculoRejeitado($cursoTutelado);
                    $this->tutelaService->converterParaTutelaPropria(
                        $cursoTutelado,
                        (string) $instituicao->getKey(),
                    );
                }

            }
            if ($tenantTutorId) {
                $tenantTutor = Tenant::query()->findOrFail($tenantTutorId);

                $dadosTutor = $tenantTutor->run(function () use ($validated): array {
                    return [
                        'classes' => \App\Models\Tenant\Classe::query()
                            ->whereIn('id', $validated['classes'])
                            ->get(['id', 'nome', 'nivel_ensino']),
                        'nivelEnsino' => \App\Models\Tenant\NivelEnsino::query()
                            ->find($validated['nivel_ensino_id'], ['id', 'nome']),
                    ];
                });

                // Nível — busca pelo nome localmente e substitui o ID
                if ($dadosTutor['nivelEnsino']) {
                    $nivelLocal = \App\Models\Tenant\NivelEnsino::query()->firstOrCreate(
                        ['nome' => $dadosTutor['nivelEnsino']->nome],
                    );
                    $validated['nivel_ensino_id'] = (string) $nivelLocal->getKey();
                }

                // Classes — busca pelo nome localmente e substitui os IDs
                $classeIds = [];
                foreach ($dadosTutor['classes'] as $classe) {
                    $classeLocal = \App\Models\Tenant\Classe::query()->firstOrCreate(
                        ['nome' => $classe->nome],
                        ['nivel_ensino' => $classe->nivel_ensino],
                    );
                    $classeIds[] = (string) $classeLocal->getKey();
                }
                $validated['classes'] = $classeIds;
            }

            $cursoTutelado->classes()->sync(
                collect($validated['classes'])
                    ->mapWithKeys(fn(string $classeId): array => [
                        $classeId => [
                            'nivel_ensino_id' => $validated['nivel_ensino_id'],
                        ],
                    ])
                    ->all()
            );
        });
    }
}
