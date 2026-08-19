<?php

namespace App\Models\central;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PeriodoLancamentoNotas extends Model
{
    use HasUuid;

    protected $fillable = [
        'instituicao_id',
        'periodo',
        'ano_lectivo_id',
        'data_inicio',
        'data_limite',
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_limite' => 'datetime',
    ];

    public function dentroDoPrazo(): bool
    {
        return now()->between($this->data_inicio, $this->data_limite);
    }
}
