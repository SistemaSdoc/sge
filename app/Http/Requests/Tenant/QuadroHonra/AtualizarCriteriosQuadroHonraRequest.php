<?php

namespace App\Http\Requests\Tenant\QuadroHonra;

use App\Models\Tenant\QuadroHonra;
use App\Models\Tenant\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AtualizarCriteriosQuadroHonraRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('tenant');

        return $user instanceof User
            && $user->can('updateCriteria', QuadroHonra::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'media_minima' => ['required', 'numeric', 'min:14', 'max:20'],
            'limite_alunos' => ['required', 'integer', 'min:1', 'max:65535'],
        ];
    }
}
