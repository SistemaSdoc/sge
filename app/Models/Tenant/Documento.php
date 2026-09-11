<?php

namespace App\Models\Tenant;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasUuid;

    protected $fillable = [
        'item_pagavel_id',
        'instituicao_id',
        'subtipo',
        'template_path',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function itemPagavel()
    {
        return $this->belongsTo(ItemPagavel::class);
    }

    public function instituicao()
    {
        return $this->belongsTo(Instituicao::class);
    }
}
