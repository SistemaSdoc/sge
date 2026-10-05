<?php

namespace App\Http\Requests\Tenant\Inscricao;

use App\Models\Tenant\CursoClasseTurno;
use App\Rules\CentralAnoLectivoExists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ─── Obrigatórios na inscrição ───
            'nome' => 'required|string|max:255',
            'bi' => [
                'required',
                'string',
                'max:20',
                'unique:candidatos,bi',
                'unique:users,bi',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:candidatos,email',
                'unique:users,email',
            ],

            'curso_classe_turno_id' => [
                'required',
                'exists:curso_classe_turno,id',
            ],

            'turma_id' => [
                'nullable',
                'exists:turmas,id',
            ],

            'nota_teste' => [
                'required',
                'numeric',
                'min:0',
                'max:20',
            ],

            'ano_lectivo_id' => [
                'nullable',
                'uuid',
                new CentralAnoLectivoExists,
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('curso_classe_turno_id')) {
                return;
            }

            $cursoClasseTurno = CursoClasseTurno::query()
                ->with('cursoClasse.cursoTutelado.instituicaoCurso.curso')
                ->find($this->input('curso_classe_turno_id'));

            $curso = $cursoClasseTurno
                ?->cursoClasse
                ?->cursoTutelado
                ?->instituicaoCurso
                ?->curso;

            if ($curso?->trashed()) {
                $validator->errors()->add(
                    'curso_classe_turno_id',
                    'O curso selecionado está arquivado e não pode receber novas matrículas.'
                );
            }
        }];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'bi.required' => 'O número de BI é obrigatório.',
            'bi.unique' => 'Já existe um registo com este número de BI.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email introduzido não é válido.',
            'email.unique' => 'Já existe um registo com este email.',
            'curso_classe_turno_id.required' => 'O curso/turno é obrigatório.',
            'curso_classe_turno_id.exists' => 'O curso/turno seleccionado não existe.',
            'turma_id.exists' => 'A turma seleccionada não existe.',
            'nota_teste.numeric' => 'A nota deve ser numérica.',
            'nota_teste.min' => 'A nota não pode ser inferior a 0.',
            'nota_teste.max' => 'A nota não pode ser superior a 20.',
            'nota_teste.required' => 'A nota é obrigatória.',
        ];
    }
}
