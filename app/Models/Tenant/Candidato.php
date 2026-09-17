<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'nome',
    'bi',
    'email',
    'genero',
    'nacionalidade',
    'naturalidade',
    'filiacao',
    'data_nascimento',
    'numero_estudante',
    'telefone',
    'municipio',
    'morada',
    'perfil_completo',
])]
class Candidato extends Model
{
    use HasUuid;

    protected $table = 'candidatos';

    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'perfil_completo' => 'boolean',
        ];
    }

    // ============================================================
    // RELAÇÕES
    // ============================================================

    /**
     * Inscrição principal do candidato.
     *
     * Como o schema tem `candidato_id` em `inscricoes`, o candidato
     * tem uma inscrição (hasOne). Se um dia tiver várias, muda para hasMany.
     */
    public function inscricoes()
    {
        return $this->hasOne(Inscricao::class, 'candidato_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ============================================================
    // ACESSORES
    // ============================================================

    protected function generoLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->genero) {
                'M'     => 'Masculino',
                'F'     => 'Feminino',
                default => '—',
            },
        );
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public function temPerfilCompleto(): bool
    {
        return (bool) $this->perfil_completo;
    }
}