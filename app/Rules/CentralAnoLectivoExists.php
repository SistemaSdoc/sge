<?php

namespace App\Rules;

use App\Models\Central\AnoLectivo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CentralAnoLectivoExists implements ValidationRule
{
    public function __construct(private readonly bool $includeTrashed = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = $this->includeTrashed
            ? AnoLectivo::withTrashed()
            : AnoLectivo::query();

        if (! $query->whereKey($value)->exists()) {
            $fail('O ano lectivo seleccionado não existe na central.');
        }
    }
}
