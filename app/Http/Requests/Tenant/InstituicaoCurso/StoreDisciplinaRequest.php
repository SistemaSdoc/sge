<?php

namespace App\Http\Requests\Tenant\InstituicaoCurso;

use App\Models\Tenant\Disciplina;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisciplinaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disciplina_id' => ['required', Rule::exists(Disciplina::class, 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'disciplina_id.required' => 'A disciplina é obrigatória.',
            'disciplina_id.exists' => 'A disciplina selecionada é inválida.',
        ];
    }
}
