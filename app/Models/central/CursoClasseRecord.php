<?php

namespace App\Models\central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class CursoClasseRecord extends Model
{
    use HasUuid;

    protected $table = 'curso_classe';

    public $incrementing = false;

    public $keyType = 'string';

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function cursoTutelado()
    {
        return $this->belongsTo(CursoTutelado::class, 'curso_tutelado_id');
    }

    public function cursoClasseTurnos()
    {
        return $this->hasMany(CursoClasseTurno::class, 'curso_classe_id');
    }
}
