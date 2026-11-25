<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

#[Fillable([
    'token_hash',
    'ip_origem',
    'valida',
    'verificado_em',
])]
class VerificacaoDeclaracaoEstagio extends Model
{
    use CentralConnection;

    protected $table = 'verificacoes_declaracoes_estagio';

    protected function casts(): array
    {
        return [
            'valida' => 'boolean',
            'verificado_em' => 'datetime',
        ];
    }
}
