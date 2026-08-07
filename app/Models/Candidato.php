<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nome',
    'bi',
    'numero_estudante',
    'morada',
    'telefone',
    'email',
    'user_id',
    'genero',
    'nacionalidade',
    'naturalidade',
    'filiacao',
    'data_nascimento',
])]
class Candidato extends Model
{
    use HasUuid;

    protected $table = 'candidatos';

    protected $primaryKey = 'id';

    public function inscricoes()
    {
        return $this->hasOne(Inscricao::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function generoLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->genero === 'M' ? 'Masculino' : 'Feminino',
        );
    }
}
