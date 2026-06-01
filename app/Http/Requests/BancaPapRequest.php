<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BancaPapRequest extends FormRequest
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
            'grupo_pap_id' => 'required|exists:grupo_pap,id',
            'presidente' => 'required|exists:professores,id',
            'vogal1' => 'required|exists:professores,id',
            'vogal2' => 'required|exists:professores,id',
        ];
    }

    public function messages(): array
    {
        return [
            'grupo_pap_id.required' => 'O campo grupo_pap_id é obrigatório.',
            'grupo_pap_id.exists' => 'O grupo_pap_id fornecido não existe.',
            'presidente.required' => 'O campo presidente é obrigatório.',
            'presidente.exists' => 'O professor presidente fornecido não existe.',
            'vogal1.required' => 'O campo vogal1 é obrigatório.',
            'vogal1.exists' => 'O professor vogal1 fornecido não existe.',
            'vogal2.required' => 'O campo vogal2 é obrigatório.',
            'vogal2.exists' => 'O professor vogal2 fornecido não existe.',
        ];
    }
}
