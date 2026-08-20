<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'trabalho_pap_id',
    'submetido_por_id',
    'numero_versao',
    'caminho_ficheiro',
    'nome_original',
    'status_quando_submetido',
])]
class TrabalhoPapVersao extends Model
{
    use HasUuid;

    protected $table = 'trabalho_pap_versoes';

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

    public function submetidoPor()
    {
        return $this->belongsTo(User::class, 'submetido_por_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(TrabalhoPapFeedback::class, 'versao_id')
            ->latest();
    }
}