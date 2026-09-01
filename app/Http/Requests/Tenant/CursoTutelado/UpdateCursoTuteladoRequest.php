<?php

namespace App\Http\Requests\Tenant\CursoTutelado;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida a actualização de um curso tutelado.
 */
class UpdateCursoTuteladoRequest extends FormRequest
{
    /**
     * A autorização final é feita pela policy do controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de duração, classes e tutela.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $cursoTutelado = $this->route('cursoTutelado');
        $cursoId = $cursoTutelado?->instituicaoCurso?->curso_id
            ?? $this->input('curso_id');

        return [
            'nome' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('cursos', 'nome')->ignore($cursoId),
            ],
            'tenant_tutor_id' => ['nullable', 'string'],
            'duracao_anos' => ['required', 'integer', 'min:1', 'max:10'],
            'nivel_ensino_id' => ['required', 'uuid', 'exists:niveis_ensino,id'],
            'classes' => ['required', 'array', 'min:1'],
            'classes.*' => ['string', 'exists:classes,id'],
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
            'nome.unique' => 'Já existe um curso com este nome.',
            'nome.required' => 'O nome do curso é obrigatório.',
            'nome.min' => 'O nome do curso deve ter pelo menos 2 caracteres.',
            'tenant_tutor_id.string' => 'A instituição tutora seleccionada é inválida.',
            'duracao_anos.required' => 'A duração do curso é obrigatória.',
            'duracao_anos.integer' => 'A duração do curso deve ser um número inteiro.',
            'duracao_anos.min' => 'A duração do curso deve ser de pelo menos 1 ano.',
            'duracao_anos.max' => 'A duração do curso não pode ultrapassar 10 anos.',
            'nivel_ensino_id.required' => 'Seleccione o nível de ensino.',
            'nivel_ensino_id.exists' => 'O nível de ensino seleccionado não existe.',
            'classes.required' => 'Seleccione pelo menos uma classe.',
            'classes.array' => 'A lista de classes seleccionada é inválida.',
            'classes.min' => 'Seleccione pelo menos uma classe.',
            'classes.*.string' => 'Uma das classes seleccionadas é inválida.',
            'classes.*.exists' => 'Uma das classes seleccionadas já não existe.',
        ];
    }
}
