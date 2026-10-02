<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasSearch
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '' || empty($this->searchable)) {
            return $query;
        }

        $pattern = '%'.addcslashes($term, '\\%_').'%';

        return $query->where(function (Builder $query) use ($pattern): void {
            foreach ($this->searchable as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $relatedColumn] = explode('.', $column, 2);

                    $query->orWhereHas($relation, fn (Builder $relatedQuery) => $relatedQuery->where($relatedColumn, 'like', $pattern));

                    continue;
                }

                $query->orWhere($column, 'like', $pattern);
            }
        });
    }
}
