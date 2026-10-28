<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'curso_tutelado_id',
    'criado_por',
    'titulo',
    'descricao',
    'ativo',
    'estado_aprovacao',
    'observacao_aprovacao',
    'aprovado_por',
    'aprovado_em',
])]
class SugestaoTemaPap extends Model
{
    use HasUuid;

    protected $table = 'sugestoes_temas_pap';

    protected $casts = [
        'ativo' => 'boolean',
        'aprovado_em' => 'datetime',
    ];

    public function cursoTutelado()
    {
        return $this->belongsTo(CursoTutelado::class, 'curso_tutelado_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function aprovadoPor()
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }

    // Helpers de estado
    public function isPendente(): bool
    {
        return $this->estado_aprovacao === 'pendente';
    }

    public function isAprovada(): bool
    {
        return $this->estado_aprovacao === 'aprovada';
    }

    public function isMelhoriasolicitada(): bool
    {
        return $this->estado_aprovacao === 'melhoria_solicitada';
    }
}
