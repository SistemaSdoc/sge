<?php

namespace App\Http\Requests\ItemPagavel;

use Illuminate\Foundation\Http\FormRequest;
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
            'nome'          => 'sometimes|required|string|max:255',
            'descricao'     => 'nullable|string',
            'curso_classe_id' => 'sometimes|required|exists:curso_classe,id',
            'valor'         => 'sometimes|required|numeric|min:0',
            'frequencia'    => 'sometimes|required|in:unico,mensal,anual',
            'ativo'         => 'sometimes|boolean',
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
}