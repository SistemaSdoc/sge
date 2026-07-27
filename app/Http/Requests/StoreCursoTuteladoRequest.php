<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCursoTuteladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'curso_id' => ['nullable', 'uuid', 'exists:cursos,id'],
            'nome' => ['nullable', 'string', 'min:2', 'max:255'],
            'duracao_anos' => ['nullable', 'integer', 'min:1', 'max:10'],
            'nivel_ensino_id' => ['required', 'uuid', 'exists:niveis_ensino,id'],
            'classe_ids' => ['required', 'array', 'min:1'],
            'classe_ids.*' => ['uuid', 'exists:classes,id'],
        ];

        // Se não veio curso_id, nome e duração tornam-se obrigatórios
        if (empty($this->curso_id)) {
            $rules['nome'] = ['required', 'string', 'min:2', 'max:255'];
            $rules['duracao_anos'] = ['required', 'integer', 'min:1', 'max:10'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'curso_id.uuid' => 'O curso seleccionado é inválido.',
            'curso_id.exists' => 'O curso seleccionado não existe.',
            'nome.required' => 'O nome do curso é obrigatório quando não selecciona um curso existente.',
            'nome.min' => 'O nome do curso deve ter pelo menos 2 caracteres.',
            'duracao_anos.required' => 'A duração é obrigatória quando não selecciona um curso existente.',
            'duracao_anos.min' => 'A duração deve ser pelo menos 1 ano.',
            'nivel_ensino_id.required' => 'Seleccione o nível de ensino.',
            'nivel_ensino_id.exists' => 'O nível de ensino seleccionado não existe.',
            'classe_ids.required' => 'Seleccione pelo menos uma classe.',
            'classe_ids.min' => 'Seleccione pelo menos uma classe.',
            'classe_ids.*.uuid' => 'Uma ou mais classes seleccionadas são inválidas.',
            'classe_ids.*.exists' => 'Uma ou mais classes seleccionadas não existem.',
        ];
    }
}
