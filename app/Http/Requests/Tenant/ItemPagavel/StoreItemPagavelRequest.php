<?php

namespace App\Http\Requests\Tenant\ItemPagavel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemPagavelRequest extends FormRequest
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
    { {
            $isInstituto = auth()->user()->instituicao?->tipo === 'instituto';


            return [
                'nome' => ['required', 'string', 'max:255'],
                'tipo' => $isInstituto ? ['nullable'] : ['required', Rule::in(['financeiro', 'documento'])],
                'valor' => $isInstituto ? ['nullable'] : ['required', 'numeric', 'min:0', 'max:9999999.99'],
                'frequencia' => $isInstituto ? ['nullable'] : ['required', Rule::in(['mensal', 'anual', 'unico'])],

                'subtipo' => [
                    Rule::requiredIf($this->input('tipo') === 'documento'),
                    'nullable',
                    Rule::in(['declaracao_sem_notas', 'declaracao_com_notas', 'certificado']),
                    // Único por instituição — não pode ter dois documentos com o mesmo subtipo
                    Rule::unique('documentos', 'subtipo')
                        ->where('instituicao_id', auth()->user()->instituicao_id),
                ],
                'descricao' => ['nullable', 'string', 'max:255'],
                'curso_classe_id' => ['nullable', 'uuid', 'exists:curso_classe,id'],
                'ativo' => ['boolean'],
            ];
        }

    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do item é obrigatório.',
            'tipo.required' => 'Selecciona o tipo do item.',
            'tipo.in' => 'Tipo inválido. Use financeiro ou documento.',
            'valor.required' => 'O valor é obrigatório.',
            'valor.min' => 'O valor não pode ser negativo.',
            'frequencia.required' => 'Selecione a frequência de pagamento.',
            'frequencia.in' => 'Frequência inválida. Use mensal, anual ou único.',
            'curso_classe_id.exists' => 'Curso/classe inválido.',
            'subtipo.required' => 'Seleccione o subtipo do documento.',
            'subtipo.in' => 'Subtipo inválido.',
            'subtipo.unique' => 'Já existe este documento.',
        ];
    }
}
