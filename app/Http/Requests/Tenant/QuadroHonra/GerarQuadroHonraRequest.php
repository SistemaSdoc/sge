<?php

namespace App\Http\Requests\Tenant\QuadroHonra;

use App\Models\Tenant\QuadroHonra;
use App\Models\Tenant\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Valida os filtros da pré-visualização e delega a autorização à policy do quadro. */
class GerarQuadroHonraRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('tenant');

        return $user instanceof User && $user->can('generate', QuadroHonra::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'periodo' => ['required', 'integer', 'in:1,2,3'],
            // ALTERAÇÃO AQUI: Força a validação na conexão central
            'ano_lectivo_id' => ['required', 'uuid', 'exists:central.ano_lectivos,id'],
            'media_minima' => ['sometimes', 'numeric', 'min:14', 'max:20'],
            'limite_alunos' => ['sometimes', 'integer', 'min:1', 'max:65535'],
        ];
    }
}
