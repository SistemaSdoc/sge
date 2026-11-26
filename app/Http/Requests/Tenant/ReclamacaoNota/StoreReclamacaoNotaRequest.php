<?php

namespace App\Http\Requests\Tenant\ReclamacaoNota;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReclamacaoNotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nota_id' => ['required', 'uuid', Rule::exists('notas', 'id')],
            'componente' => ['required', 'in:mac,nota_prova_professor,nota_prova_trimestral'],
            'nota_reclamada' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
            'anexos' => ['nullable', 'array', 'max:5'],
            'anexos.*' => ['file', 'mimes:pdf,png,jpg,jpeg,doc,docx', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nota_id.required' => 'Selecione uma nota para reclamar.',
            'nota_id.exists' => 'A nota selecionada já não está disponível.',
            'componente.required' => 'Selecione o componente da nota.',
            'componente.in' => 'Selecione um componente válido.',
            'nota_reclamada.numeric' => 'A nota que considera correta deve ser um número.',
            'nota_reclamada.min' => 'A nota não pode ser menor que 0.',
            'nota_reclamada.max' => 'A nota não pode ser maior que 20.',
            'justificativa.required' => 'Explique o motivo da reclamação.',
            'justificativa.min' => 'A justificativa deve conter pelo menos 10 caracteres.',
            'justificativa.max' => 'A justificativa não pode ter mais que 2000 caracteres.',
            'anexos.max' => 'Pode anexar no máximo 5 ficheiros.',
            'anexos.*.file' => 'Selecione um ficheiro válido.',
            'anexos.*.mimes' => 'Os anexos devem ser PDF, PNG, JPG, DOC ou DOCX.',
            'anexos.*.max' => 'Cada anexo pode ter no máximo 10 MB.',
        ];
    }
}
