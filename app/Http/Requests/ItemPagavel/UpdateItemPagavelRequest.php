<?php

namespace App\Http\Requests\ItemPagavel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateItemPagavelRequest extends FormRequest
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
            //
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
