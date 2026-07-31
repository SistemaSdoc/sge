<?php

namespace App\Models;

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
        'data_inicio' => 'date',
        'data_limite' => 'date',
    ];

    public function dentroDoPrazo(): bool
    {
        return today()->between($this->data_inicio, $this->data_limite);
    }
}
