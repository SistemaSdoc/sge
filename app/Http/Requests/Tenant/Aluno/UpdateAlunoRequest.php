<?php

namespace App\Http\Requests\Tenant\Aluno;

use App\Models\Tenant\Aluno;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Valida e autoriza a edição dos dados pessoais e da turma do aluno. */
class UpdateAlunoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $aluno = $this->route('aluno');

        return $aluno instanceof Aluno && Gate::allows('update', $aluno);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $aluno = $this->route('aluno');
        $candidato = $aluno?->inscricao?->candidato;
        abort_if($candidato === null, 404);

        $user = $aluno->user ?? $candidato->user;
        $userBiRule = Rule::unique('users', 'bi');
        $userEmailRule = Rule::unique('users', 'email');

        if ($user !== null) {
            $userBiRule->ignore($user->getKey());
            $userEmailRule->ignore($user->getKey());
        }

        return [
            'nome' => ['required', 'string', 'max:255'],
            'bi' => [
                'required',
                'string',
                'max:20',
                Rule::unique('candidatos', 'bi')->ignore($candidato),
                $userBiRule,
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('candidatos', 'email')->ignore($candidato),
                $userEmailRule,
            ],
            'turma_id' => ['nullable', 'exists:turmas,id'],
        ];
    }
}
