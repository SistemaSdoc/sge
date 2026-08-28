<?php

namespace App\Models\Central;

use App\Models\Tenant\Curso;
use App\Models\Tenant\Instituicao;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'instituicao_tutora_id',
    'instituicao_tutelada_id',
    'curso_id',
    'ativo',
])]
class Tutela extends Model
{
    use HasUuid;

    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection', config('database.default'));
    }

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function instituicaoTutora(): BelongsTo
    {
        return $this->belongsTo(Instituicao::class, 'instituicao_tutora_id');
    }

    public function instituicaoTutelada(): BelongsTo
    {
        return $this->belongsTo(Instituicao::class, 'instituicao_tutelada_id');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }
}
