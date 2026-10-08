<?php

namespace App\Models\Tenant;

use App\Contracts\HasUserCleanup;
use App\Exceptions\UserRemovalBlockedException;
use App\Traits\HasSearch;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'especialidade',
    'nivel_academico',
])]

class Professor extends Model implements HasUserCleanup
{
    use HasSearch, HasUuid, SoftDeletes;

    public function cleanupOnUserRemoval(): void
    {
        $courseCount = $this->cursosTutelados()->count();
        $disciplineCount = $this->turmaDisciplinaProfessor()->count();
        $papGroupCount = $this->gruposPap()->count();
        $assignments = array_filter([
            $courseCount ? "{$courseCount} curso(s)" : null,
            $disciplineCount ? "{$disciplineCount} atribuição(ões) a disciplina/turma" : null,
            $papGroupCount ? "{$papGroupCount} grupo(s) de PAP" : null,
        ]);

        if ($assignments !== []) {
            throw new UserRemovalBlockedException(
                'Não é possível remover o professor enquanto estiver associado a '
                .implode(', ', $assignments)
                .'. Remova ou reatribua essas ligações primeiro.'
            );
        }

        SolicitacaoEdicaoPauta::where('professor_user_id', $this->user_id)
            ->where('status', 'pendente')
            ->delete();

        $this->delete();
    }

    protected array $searchable = ['especialidade', 'nivel_academico', 'user.nome'];

    protected $table = 'professores';

    protected $primaryKey = 'id';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function turmas()
    {
        return $this->belongsToMany(Turma::class, 'turma_disciplina_professor', 'professor_id', 'turma_id')
            ->using(TurmaDisciplinaProfessor::class)
            ->withPivot('classe_turno_disciplina_id')
            ->withTimestamps();
    }

    public function disciplinas()
    {
        return $this->belongsToMany(ClasseTurnoDisciplina::class, 'turma_disciplina_professor', 'professor_id', 'classe_turno_disciplina_id')
            ->using(TurmaDisciplinaProfessor::class)
            ->withPivot('turma_id')
            ->withTimestamps();
    }

    public function gruposPap()
    {
        return $this->hasMany(GrupoPap::class, 'professor_tutor_id');
    }

    public function turnoDisciplina()
    {
        return $this->hasMany(GrupoPap::class, 'professor_tutor_id');
    }

    public function turmaDisciplinaProfessor()
    {
        return $this->hasMany(TurmaDisciplinaProfessor::class, 'professor_id');
    }

    public function classeTurnoDisciplinas()
    {
        return $this->belongsToMany(
            ClasseTurnoDisciplina::class,
            'turma_disciplina_professor',
            'professor_id',
            'classe_turno_disciplina_id'
        )
            ->using(TurmaDisciplinaProfessor::class)
            ->withPivot('turma_id')
            ->withTimestamps();
    }

    public function cursosTutelados()
    {
        return $this->belongsToMany(CursoTutelado::class, 'curso_tutelado_professor')
            ->using(CursoTuteladoProfessor::class)
            ->withPivot('tipo', 'coordenador')
            ->withTimestamps();
    }

    public function justificativas()
    {
        return $this->hasMany(JustificativaNaoSubmissao::class);
    }
}
