<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

trait HasSearch
{
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '' || empty($this->searchable)) {
            return $query;
        }

        $escaped = addcslashes($term, '\\%_');

        $match = fn ($q, string $col) => $q
            ->where($col, 'like', "{$escaped}%")
            ->orWhere($col, 'like', "% {$escaped}%");

        return $query->where(function (Builder $q) use ($match) {
            foreach ($this->searchable as $column) {
                if (! str_contains($column, '.')) {
                    $q->orWhere(fn ($w) => $match($w, $column));

                    continue;
                }

                $path = Str::beforeLast($column, '.');   // ex.: cursoTutelado.instituicaoTutora
                $col = Str::afterLast($column, '.');    // ex.: nome

                $parentPath = str_contains($path, '.') ? Str::beforeLast($path, '.') : null;
                $last = Str::afterLast($path, '.');

                // modelo onde está a última relação
                $parent = $this;
                if ($parentPath) {
                    foreach (explode('.', $parentPath) as $segment) {
                        $parent = $parent->{$segment}()->getRelated();
                    }
                }

                $rel = $parent->{$last}();

                if (
                    $rel instanceof BelongsTo
                    && $rel->getRelated()->getConnectionName() !== $parent->getConnectionName()
                ) {
                    // última relação está noutra base: pesquisa lá, filtra aqui pelos IDs
                    $ids = $rel->getRelated()->newQuery()
                        ->withoutGlobalScopes()
                        ->where(fn ($w) => $match($w, $col))
                        ->limit(500)
                        ->pluck($rel->getOwnerKeyName());

                    $fk = $rel->getForeignKeyName();

                    if ($parentPath) {
                        $q->orWhereHas($parentPath, fn ($r) => $r->whereIn($fk, $ids));
                    } else {
                        $q->orWhereIn($fk, $ids);
                    }
                } else {
                    $q->orWhereHas($path, fn ($r) => $r->where(fn ($w) => $match($w, $col)));
                }
            }
        });
    }
}
