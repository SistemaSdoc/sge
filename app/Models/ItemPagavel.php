<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemPagavel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'itens_pagaveis';

    protected $fillable = [
        'instituicao_id',
        'curso_classe_id',
        'nome',
        'descricao',
        'valor',
        'frequencia',
        'ativo',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    public function instituicao(): BelongsTo
    {
        return $this->belongsTo(Instituicao::class);
    }

    public function cursoClasse(): BelongsTo
    {
        return $this->belongsTo(CursoClasse::class, 'curso_classe_id');
    }

    public function periodosPagos(): HasMany
    {
        return $this->hasMany(PagamentoPeriodo::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeUniversais(Builder $query): Builder
    {
        return $query->whereNull('curso_classe_id');
    }
}
