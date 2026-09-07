<?php

namespace App\Http\Resources\Tenant\CursoTutelado;

use App\Enums\TutelaStatus;
use App\Models\Central\CursoTuteladoShared;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CursoTuteladoResourceEdit extends JsonResource
{
    public function toArray(Request $request): array
    {
        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        // Tutela activa — o que está aprovado actualmente
        $sharedActivo = CursoTuteladoShared::on($centralConnection)
            ->where('curso_tutelado_tutelado_id', $this->getKey())
            ->where('status', TutelaStatus::ACTIVO)
            ->latest('updated_at')
            ->first();

        // Pedido pendente — se existir, mostra no formulário como "a aguardar aprovação"
        $sharedPendente = CursoTuteladoShared::on($centralConnection)
            ->where('curso_tutelado_tutelado_id', $this->getKey())
            ->whereIn('status', [
                TutelaStatus::PENDENTE,
                TutelaStatus::PENDENTE_TROCA,
            ])
            ->latest()
            ->first();

        $sharedExibido = $sharedPendente?->status === TutelaStatus::PENDENTE
            ? $sharedPendente
            : $sharedActivo;

        return [
            'id' => $this->id,
            'curso_id' => $this->instituicaoCurso->curso_id,
            'curso' => [
                'nome' => $this->instituicaoCurso->curso->nome,
                'duracao_anos' => $this->instituicaoCurso->duracao_anos,
            ],
            'nivel_ensino_id' => (string) ($this->cursoClasses->first()?->nivel_ensino_id ?? ''),
            'nivel_ensino' => $this->cursoClasses->first()?->nivelEnsino ? [
                'id' => (string) $this->cursoClasses->first()->nivelEnsino->id,
                'nome' => $this->cursoClasses->first()->nivelEnsino->nome,
            ] : null,
            'tipo_tutela' => $this->tipo_tutela ?? 'propria',

            // Tutela actual (activa) — o que o select deve mostrar por defeito
            'tenant_tutor_id' => $sharedActivo?->tenant_tutor_id
                ?? $this->cursoTuteladoShared?->tenant_tutor_id,

            'instituicao_tutora' => $sharedExibido
                ? ['id' => $sharedExibido->tenant_tutor_id, 'nome' => $sharedExibido->tenant_tutor_nome]
                : ($this->instituicaoTutora
                    ? ['id' => $this->instituicaoTutora->id, 'nome' => $this->instituicaoTutora->nome]
                    : null),

            // Pedido pendente — para mostrar aviso no frontend "Troca pendente para X"
            'tutela_pendente' => $sharedPendente ? [
                'tenant_tutor_id' => $sharedPendente->tenant_tutor_id,
                'tenant_tutor_nome' => $sharedPendente->tenant_tutor_nome,
                'status' => $sharedPendente->status->value,
            ] : null,

            'classes' => $this->classes->pluck('id'),
        ];
    }
}
