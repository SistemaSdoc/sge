<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['professor_tutor_id', 'turma_id', 'nome_grupo', 'tema_grupo', 'estudo_caso', 'trabalho_grupo', 'status', 'nota_final', 'data_defesa', 'local_defesa'])]
class GrupoPap extends Model
{
    use HasUuid;

    protected $table = 'grupo_pap';

    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'data_defesa' => 'date:Y-m-d',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_tutor_id');
    }

    public function jurados()
    {
        return $this->hasMany(BancaJuriPap::class);
    }

    public function turma()
    {
        return $this->belongsTo(Turma::class, 'turma_id');
    }

    public function elementos()
    {
        return $this->hasMany(ElementoGrupoPap::class, 'grupo_pap_id');
    }

    public function alunos()
    {
        return $this->belongsToMany(Aluno::class, 'elementos_grupo_pap', 'grupo_pap_id', 'aluno_id');
    }
}
