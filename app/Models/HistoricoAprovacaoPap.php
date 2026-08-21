<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoAprovacaoPap extends Model
{
    use HasUuid;

    protected $table = 'historico_aprovacao_pap';

    protected $primaryKey = 'id';

    protected $fillable = [
        'grupo_pap_id',
        'utilizador_id',
        'tema',
        'problema',      
        'objectivos',    
        'estado_anterior',
        'estado_novo',
        'comentario',
    ];

    public function grupoPap(): BelongsTo
    {
        return $this->belongsTo(
            GrupoPap::class,
            'grupo_pap_id'
        );
    }

    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'utilizador_id'
        );
    }
}