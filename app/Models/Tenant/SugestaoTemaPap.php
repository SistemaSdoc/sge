<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['curso_tutelado_id', 'titulo', 'descricao', 'ativo'])]
class SugestaoTemaPap extends Model
{
    use HasUuid;

    protected $table = 'sugestoes_temas_pap';

    protected $casts = ['ativo' => 'boolean'];
}
