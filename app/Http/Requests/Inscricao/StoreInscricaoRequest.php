<?php

namespace App\Http\Requests\Inscricao;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInscricaoRequest extends FormRequest
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
            'nome'                 => 'required|string|max:255',
            'bi'                   => 'required|string|max:20',
            'numero_estudante'     => 'required|string|max:20',
            'telefone'             => 'nullable|string|max:20',
            'email'                => 'required|email|max:255|unique:candidatos,email|unique:users,email',
            'curso_classe_turno_id'=> 'required|exists:curso_classe_turno,id',
        ];
    }
}
