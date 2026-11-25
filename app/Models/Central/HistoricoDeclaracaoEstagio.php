<?php

namespace App\Models\Central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tenant_id',
    'aluno_id',
    'aluno_nome',
    'matricula',
    'gerado_por_id',
    'emitido_em',
])]
class HistoricoDeclaracaoEstagio extends Model
{
    use HasUuid;

    protected $table = 'historico_declaracoes_estagio';

    protected function casts(): array
    {
        return [
            'emitido_em' => 'datetime',
        ];
    }
}
