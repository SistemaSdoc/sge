<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'trabalho_pap_id',
    'versao_id',
    'utilizador_id',
    'tipo',
    'comentario',
    'caminho_ficheiro_correcao',
    'nome_original_correcao',
    'estado_anterior',
    'estado_novo',
])]
class TrabalhoPapFeedback extends Model
{
    use HasUuid;

    protected $table = 'trabalho_pap_feedbacks';

    const TIPO_CORRECAO_TUTOR = 'correcao_tutor';

    const TIPO_APROVACAO_TUTOR = 'aprovacao_tutor';

    const TIPO_CORRECAO_COORDENACAO = 'correcao_coordenacao';

    const TIPO_APROVACAO_COORDENACAO = 'aprovacao_coordenacao';

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function trabalhoPap()
    {
        return $this->belongsTo(TrabalhoPap::class, 'trabalho_pap_id');
    }

    public function versao()
    {
        return $this->belongsTo(TrabalhoPapVersao::class, 'versao_id');
    }

    public function utilizador()
    {
        return $this->belongsTo(User::class, 'utilizador_id');
    }
}
