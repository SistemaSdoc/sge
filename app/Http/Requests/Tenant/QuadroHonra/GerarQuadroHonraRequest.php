<?php

namespace App\Http\Requests\Tenant\QuadroHonra;

use App\Enums\TipoQuadroHonra;
use App\Models\Tenant\QuadroHonra;
use App\Models\Tenant\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valida os critérios da geração e delega a autorização à policy do quadro. */
class GerarQuadroHonraRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('tenant');
        $tipo = $this->input('tipo');
        $cursoTuteladoId = $this->input('curso_tutelado_id');

        return $user instanceof User
            && $user->can('generate', [
                QuadroHonra::class,
                is_string($tipo) ? $tipo : '',
                is_string($cursoTuteladoId) ? $cursoTuteladoId : null,
            ]);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(array_map(
                fn (TipoQuadroHonra $tipo): string => $tipo->value,
                TipoQuadroHonra::cases(),
            ))],
            'ano_lectivo_id' => ['required', 'uuid', 'exists:ano_lectivos,id'],
            'curso_tutelado_id' => [
                'nullable',
                'uuid',
                'exists:curso_tutelado,id',
                'required_if:tipo,curso',
                'prohibited_if:tipo,geral',
            ],
            'media_minima' => ['sometimes', 'numeric', 'min:14', 'max:20'],
            'limite_alunos' => ['sometimes', 'integer', 'min:1', 'max:65535'],
        ];
    }
}
