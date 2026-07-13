<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemPagavel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'item_pagaveis';

    protected $fillable = [
        'nome',
        'tipo',
        'valor_padrao',
        'instituicao_id',
        'status',
    ];

    protected $casts = [
        'valor_padrao' => 'decimal:2',
    ];

    public function instituicao(): BelongsTo
    {
        return $this->belongsTo(Instituicao::class);
    }

    public function propinas(): HasMany
    {
        return $this->hasMany(Propina::class);
    }
}