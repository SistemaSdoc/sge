<?php

namespace App\Http\Requests\Tenant\CursoTutelado;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSugestaoTemaPapRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sugestoes_temas_pap')
                    ->where('curso_tutelado_id', $this->route('cursoTutelado')->getKey())
                    ->ignore($this->route('sugestao')->getKey()),
            ],
            'descricao' => ['nullable', 'string'],
        ];
    }

    /**
     * Mensagens apresentadas ao utilizador durante a validação.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulo.required' => 'O título da sugestão é obrigatório.',
            'titulo.unique' => 'Este título já foi cadastrado para este curso.',
        ];
    }
}
