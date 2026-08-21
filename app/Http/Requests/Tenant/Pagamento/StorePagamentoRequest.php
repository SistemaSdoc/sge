<?php

namespace App\Http\Requests\Tenant\Pagamento;

use App\Models\Tenant\ItemPagavel;
use App\Models\Tenant\PagamentoItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // $this->user()->can('create', \App\Models\Pagamento::class);
    }

    public function rules(): array
    {
        return [
            'aluno_id' => ['required', 'uuid', 'exists:alunos,id'],
            'data_pagamento' => ['required', 'date'],
            'metodo' => ['required', Rule::in(['dinheiro', 'transferencia', 'multicaixa', 'outro'])],
            'referencia' => ['nullable', 'string', 'max:100'],
            'observacoes' => ['nullable', 'string', 'max:500'],

            'itens' => ['required', 'array', 'min:1'],
            'itens.*.item_pagavel_id' => ['required', 'uuid', 'exists:itens_pagaveis,id'],
            'itens.*.ano' => ['required', 'integer', 'min:2000', 'max:2100'],
            'itens.*.meses' => ['nullable', 'array'],
            'itens.*.meses.*' => ['integer', 'min:1', 'max:12'],
            'itens.*.valor' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'aluno_id.required' => 'Selecione o aluno.',
            'itens.required' => 'Adicione pelo menos um item ao pagamento.',
            'itens.min' => 'Adicione pelo menos um item ao pagamento.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('itens', []) as $index => $linha) {
                $item = ItemPagavel::find($linha['item_pagavel_id'] ?? null);

                if (! $item) {
                    continue;
                }

                if ($item->frequencia === 'mensal' && empty($linha['meses'])) {
                    $validator->errors()->add(
                        "itens.$index.meses",
                        "Selecione pelo menos um mês para o item '{$item->nome}'."
                    );

                    continue;
                }

                $meses = $item->frequencia === 'mensal' ? $linha['meses'] : [0];

                foreach ($meses as $mes) {
                    $jaExiste = PagamentoItem::query()
                        ->where('aluno_id', $this->input('aluno_id'))
                        ->where('item_pagavel_id', $item->id)
                        ->where('ano', $linha['ano'])
                        ->where('mes', $mes)
                        ->whereHas('pagamento')
                        ->exists();

                    if ($jaExiste) {
                        $validator->errors()->add(
                            "itens.$index.meses",
                            "O item '{$item->nome}' já foi pago para o período indicado."
                        );
                    }
                }

                if ($item->frequencia === 'unico') {
                    $jaPagouAlgumaVez = PagamentoItem::query()
                        ->where('aluno_id', $this->input('aluno_id'))
                        ->where('item_pagavel_id', $item->id)
                        ->whereHas('pagamento')
                        ->exists();

                    if ($jaPagouAlgumaVez) {
                        $validator->errors()->add(
                            "itens.$index",
                            "O item '{$item->nome}' é de pagamento único e já foi pago anteriormente."
                        );
                    }
                }
            }
        });
    }
}
