<?php

namespace App\Actions\Tenant\BancaJuriPap;

use App\Models\Central\AnoLectivo;
use App\Models\Central\Tenant;
use App\Models\Tenant\BancaJuriPap;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Turma;
use Illuminate\Support\Collection;

class PrepareBancaJuriPapForm
{
    /**
     * Prepara os dados necessários para os formulários de criação e edição da banca.
     *
     * @return array<string, mixed>
     */
    public function handle(
        Turma $turma,
        CursoTutelado $cursoTutelado,
        GrupoPap $grupoPap,
        ?BancaJuriPap $bancaJuriPap = null,
    ): array {
        $juradosNaBanca = $grupoPap->jurados()
            ->when($bancaJuriPap, fn ($query) => $query->whereKeyNot($bancaJuriPap->getKey()))
            ->get(['professor_id', 'professor_externo_id', 'professor_externo_tenant_id']);

        $professores = $this->resolverProfessoresDisponiveis($cursoTutelado, $juradosNaBanca);
        $professorTutor = $grupoPap->professor()->with('user:id,nome')->first();

        if (
            $professorTutor
            && ! $juradosNaBanca->contains(fn ($jurado): bool => (string) $jurado->professor_id === (string) $professorTutor->id)
            && ! $professores->contains(fn (array $professor): bool => (string) $professor['id'] === (string) $professorTutor->id
                && $professor['tenant_id'] === null
            )
        ) {
            $professores->push([
                'id' => $professorTutor->id,
                'nome' => $professorTutor->user?->nome ?? 'Sem nome',
                'externo' => false,
                'tenant_id' => null,
            ]);
        }

        return [
            'anoLectivoId' => $turma->ano_lectivo_id,
            'anosLectivos' => AnoLectivo::all(),
            'professores' => $professores,
            'funcoes' => [
                'Presidente',
                'Vogal 1',
                'Vogal 2',
            ],
        ];
    }

    /**
     * Resolve os professores disponíveis para a banca.
     * - Autotutela: professores principais do próprio curso tutelado
     * - Tutela externa: professores principais do curso tutelado no tenant tutor
     */
    private function resolverProfessoresDisponiveis(
        CursoTutelado $cursoTutelado,
        Collection $juradosNaBanca
    ): Collection {
        // Autotutela — busca professores do tenant actual
        if ($cursoTutelado->tipo_tutela !== 'externa' || ! $cursoTutelado->curso_tutelado_shared_id) {
            return $this->mapearProfessores(
                $cursoTutelado->professores()
                    ->whereNotIn('professores.id', $juradosNaBanca->pluck('professor_id')->filter())
                    ->wherePivot('tipo', 'principal')
                    ->with('user:id,nome')
                    ->get()
            );
        }

        // Tutela externa — vai buscar ao tenant tutor
        $shared = $cursoTutelado->relationLoaded('cursoTuteladoShared')
            ? $cursoTutelado->cursoTuteladoShared
            : $cursoTutelado->cursoTuteladoShared()->first();

        if (! $shared?->tenant_tutor_id || ! $shared?->curso_id) {
            return collect();
        }

        $tenantTutor = Tenant::find($shared->tenant_tutor_id);

        if (! $tenantTutor) {
            return collect();
        }

        $tenantTutorId = (string) $tenantTutor->getTenantKey();

        return $tenantTutor->run(function () use ($shared, $juradosNaBanca, $tenantTutorId): Collection {
            $cursoTuteladoTutor = CursoTutelado::query()
                ->where('tipo_tutela', 'propria')
                ->whereHas(
                    'instituicaoCurso',
                    fn ($q) => $q->where('curso_id', $shared->curso_id)
                )
                ->first();

            if (! $cursoTuteladoTutor) {
                return collect();
            }

            $juradosExternos = $juradosNaBanca
                ->filter(fn ($jurado): bool => (string) $jurado->professor_externo_tenant_id === $tenantTutorId)
                ->pluck('professor_externo_id')
                ->filter();

            return $this->mapearProfessores(
                $cursoTuteladoTutor->professores()
                    ->whereNotIn('professores.id', $juradosExternos)
                    ->wherePivot('tipo', 'principal')
                    ->with('user:id,nome')
                    ->get(),
                $tenantTutorId,
            );
        });
    }

    private function mapearProfessores(
        \Illuminate\Database\Eloquent\Collection $professores,
        ?string $tenantId = null,
    ): Collection {
        return $professores->map(fn ($professor): array => [
            'id' => $professor->id,
            'nome' => $professor->user?->nome ?? 'Sem nome',
            'externo' => $tenantId !== null,
            'tenant_id' => $tenantId,
        ])->values();
    }
}
