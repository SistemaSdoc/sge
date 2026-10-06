<?php

namespace App\Models\Tenant;

use App\Models\Central\CursoTuteladoShared;
use App\Models\Central\Tenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

#[Fillable([
    'instituicao_curso_id',
    'instituicao_tutora_id',
    'tipo_tutela',
    'curso_tutelado_shared_id',
    'criterios_pap_path',
    'manual_pt_path',
    'estrutura_trabalho_pap_path',
])]

class CursoTutelado extends Model
{
    use HasUuid;

    protected $table = 'curso_tutelado';

    public function instituicaoCurso()
    {
        return $this->belongsTo(InstituicaoCurso::class, 'instituicao_curso_id');
    }

    public function instituicaoTutora()
    {
        return $this->belongsTo(Instituicao::class, 'instituicao_tutora_id');
    }

    public function cursoTuteladoShared()
    {
        return $this->belongsTo(CursoTuteladoShared::class, 'curso_tutelado_shared_id');
    }

    public function cursoClasses()
    {
        return $this->hasMany(CursoClasse::class, 'curso_tutelado_id');
    }

    public function classes()
    {
        return $this->belongsToMany(
            Classe::class,
            'curso_classe',
            'curso_tutelado_id',
            'classe_id'
        )
            ->using(CursoClasse::class)
            ->withPivot('nivel_ensino_id')
            ->withTimestamps();
    }

    public function secretarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'curso_tutelado_secretario', 'curso_tutelado_id', 'user_id')
            ->using(CursoTuteladoSecretario::class)
            ->withPivot('id')
            ->withTimestamps();
    }

    public function professores()
    {
        return $this->belongsToMany(
            Professor::class,
            'curso_tutelado_professor',
            'curso_tutelado_id',
            'professor_id'
        )
            ->using(CursoTuteladoProfessor::class)
            ->withPivot(['id', 'tipo', 'coordenador', 'opap'])
            ->withTimestamps();
    }

    public function sugestoesTemas()
    {
        return $this->hasMany(SugestaoTemaPap::class, 'curso_tutelado_id');
    }

    public function resolverSugestoesTemas(): Collection
{
    if ($this->tipo_tutela !== 'externa' || ! $this->curso_tutelado_shared_id) {
        return $this->sugestoesTemas;
    }

    $shared = $this->relationLoaded('cursoTuteladoShared')
        ? $this->cursoTuteladoShared
        : $this->cursoTuteladoShared()->first();

    if (! $shared?->tenant_tutor_id || ! $shared?->curso_id) {
        return collect();
    }

    $tenantTutor = Tenant::find($shared->tenant_tutor_id);

    if (! $tenantTutor) {
        return collect();
    }

    return $tenantTutor->run(function () use ($shared): Collection {
        $tutor = CursoTutelado::query()
            ->where('tipo_tutela', 'propria')
            ->whereHas(
                'instituicaoCurso',
                fn ($q) => $q->where('curso_id', $shared->curso_id)
            )
            ->first();

        return $tutor
            ? $tutor->sugestoesTemas()->get(['id', 'titulo', 'descricao', 'ativo'])
            : collect();
    });
}

    /**
     * Resolve os paths dos documentos PAP.
     * Se tipo_tutela = externa, atravessa para o tenant tutor via shared.
     */
    public function resolverDocumentosPap(): array
    {
        $empty = [
            'criterios_pap_path' => null,
            'manual_pt_path' => null,
            'estrutura_trabalho_pap_path' => null,
        ];

        // Tem documentos locais — usa-os directamente
        if ($this->criterios_pap_path || $this->manual_pt_path || $this->estrutura_trabalho_pap_path || $this->sugestoes_temas_pap_path) {
            return [
                'criterios_pap_path' => $this->criterios_pap_path,
                'manual_pt_path' => $this->manual_pt_path,
                'estrutura_trabalho_pap_path' => $this->estrutura_trabalho_pap_path,
            ];
        }

        // Tutela externa — vai buscar ao tenant tutor
        if ($this->tipo_tutela !== 'externa' || !$this->curso_tutelado_shared_id) {
            return $empty;
        }

        // Usa a relação já carregada (eager) ou faz lazy load
        $shared = $this->relationLoaded('cursoTuteladoShared')
            ? $this->cursoTuteladoShared
            : $this->cursoTuteladoShared()->first();

        if (!$shared?->tenant_tutor_id || !$shared?->curso_id) {
            return $empty;
        }

        $tenantTutor = Tenant::find($shared->tenant_tutor_id);

        if (!$tenantTutor) {
            return $empty;
        }

        return $tenantTutor->run(function () use ($shared): array {
            $tutor = CursoTutelado::query()
                ->where('tipo_tutela', 'propria')
                ->whereHas(
                    'instituicaoCurso',
                    fn($q) => $q->where('curso_id', $shared->curso_id)
                )
                ->first(['criterios_pap_path', 'manual_pt_path', 'estrutura_trabalho_pap_path']);

            return [
                'criterios_pap_path' => $tutor?->criterios_pap_path,
                'manual_pt_path' => $tutor?->manual_pt_path,
                'estrutura_trabalho_pap_path' => $tutor?->estrutura_trabalho_pap_path,
            ];
        });
    }
}
