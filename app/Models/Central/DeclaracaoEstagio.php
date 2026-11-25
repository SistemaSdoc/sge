<?php

namespace App\Models\Central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'instituicao_nome',
    'instituicao_tipo',
    'aluno_id',
    'aluno_nome',
    'matricula',
    'numero_processo',
    'bi',
    'curso',
    'ano',
    'nota',
    'avaliacao',
    'responsavel_nome',
    'responsavel_cargo',
    'empresa_nome',
    'public_token',
    'gerado_por_id',
    'emitido_em',
])]
class DeclaracaoEstagio extends Model
{
    use HasUuid;

    protected $table = 'declaracoes_estagio';

    protected $hidden = ['public_token'];

    protected function casts(): array
    {
        return [
            'emitido_em' => 'datetime',
            'nota' => 'decimal:1',
        ];
    }

    protected function desempenho(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->nota === null) {
                return null;
            }

            return match (true) {
                round((float) $this->nota) >= 19 => 'Excelente',
                round((float) $this->nota) >= 17 => 'Muito bom',
                round((float) $this->nota) >= 14 => 'Bom',
                round((float) $this->nota) >= 10 => 'Suficiente',
                default => 'Insuficiente',
            };
        });
    }

    public function instituicao(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function geradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerado_por_id');
    }
}
