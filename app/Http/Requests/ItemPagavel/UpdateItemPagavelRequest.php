<?php

namespace App\Http\Requests\ItemPagavel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateItemPagavelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'sometimes|required|string|max:255',
            'descricao' => 'nullable|string',
            //'curso_classe_id' => 'sometimes|exists:curso_classe,id',
            'tipo' => ['required', Rule::in(['financeiro', 'documento'])],
            'subtipo' => [
                Rule::requiredIf($this->input('tipo') === 'documento'),
                'nullable',
                Rule::in(['declaracao_sem_notas', 'declaracao_com_notas', 'certificado']),
                Rule::unique('documentos', 'subtipo')
                    ->where('instituicao_id', auth()->user()->instituicao_id)
                    ->ignore($this->route('itemPagavel')?->documento?->id),
            ],
            'valor' => 'sometimes|required|numeric|min:0',
            'frequencia' => 'sometimes|required|in:unico,mensal,anual',
            'ativo' => 'sometimes|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $item = $this->route('item_pagavel');

            if ($item && $item->exists && $this->input('frequencia') !== $item->frequencia) {
                if ($item->periodosPagos()->exists()) {
                    $validator->errors()->add(
                        'frequencia',
                        'Não é possível alterar a frequência de um item que já possui pagamentos registados.'
                    );
                }
            }
        });
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
            'subtipo.unique' => 'Já existe um documento deste subtipo para esta instituição.',
        ];
    }
}
