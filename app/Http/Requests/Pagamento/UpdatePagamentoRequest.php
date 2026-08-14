<?php

namespace App\Http\Requests\Pagamento;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // $this->user()->can('update', $this->route('pagamento'));
    }

    public function rules(): array
    {
        return [
            'referencia' => ['nullable', 'string', 'max:100'],
            'observacoes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
