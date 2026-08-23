<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingTenantData extends Model
{
    protected $fillable = [
        'tenant_id',
        'nome',
        'sigla',
        'tipo',
        'email',
        'telefone',
        'provincia',
        'endereco',
        'status',
        'user_nome',
        'user_email',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
