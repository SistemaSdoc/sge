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
        return [
            'curso_id'       => ['nullable', 'string', 'exists:cursos,id'],
            'nome'           => ['nullable', 'string', 'min:2', 'max:255'],
            'duracao_anos'   => ['nullable', 'integer', 'min:1', 'max:10'],
            'classes'        => ['required', 'array', 'min:1'],
            'classes.*'      => ['string', 'exists:classes,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            if (empty($data['curso_id'])) {
                if (empty($data['nome']) || strlen($data['nome']) < 2) {
                    $validator->errors()->add('nome', 'Nome obrigatório quando curso_id não é fornecido');
                }
                if (empty($data['duracao_anos']) || $data['duracao_anos'] < 1) {
                    $validator->errors()->add('duracao_anos', 'Duração obrigatória quando curso_id não é fornecido');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'classes.required'   => 'Selecciona pelo menos uma classe',
            'classes.min'        => 'Selecciona pelo menos uma classe',
            'classes.*.string'  => 'Cada classe deve ser um ID válido',
            'classes.*.exists'   => 'Uma ou mais classes selecionadas não existem',
            'curso_id.exists'    => 'O curso seleccionado não existe',
            'nome.required_without' => 'O nome do curso é obrigatório',
            'nome.min'           => 'O nome do curso deve ter pelo menos 2 caracteres',
            'duracao_anos.min'   => 'A duração deve ser pelo menos 1 ano',
        ];
    }
}
