<?php

namespace App\Models\Central;

use App\Traits\HasSearch;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class CalendarioAnual extends Model
{
    use CentralConnection, HasSearch, HasUuids;

    protected array $searchable = ['ano', 'ficheiro_nome'];

    protected $fillable = [
        'ano',
        'ficheiro_path',
        'ficheiro_nome',
        'ativo',
    ];

    protected $table = 'calendarios_anuais';

    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }
}
