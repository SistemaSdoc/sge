<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InstituicoesRequest extends FormRequest
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
        $id = $this->instituicao?->id;

        return [
            'nome' => 'required|string|max:255',
            'sigla' => 'nullable|string|max:50',
            'tipo' => 'required|in:instituto,colegio',
            'email' => 'required|email|max:255|unique:instituicoes,email,'.$id,
            'telefone' => 'nullable|max:20',
            'provincia' => 'nullable|string|max:100',
            'endereco' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'descricao' => 'nullable|string|max:1000',
            'cursos' => 'nullable|array',
            'cursos.*' => 'exists:cursos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da instituição é obrigatório.',
            'tipo.required' => 'O tipo da instituição é obrigatório.',
            'tipo.in' => 'O tipo da instituição deve ser "instituto" ou "colegio".',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ser um endereço de email válido.',
            'email.unique' => 'O email já está em uso.',
            'logo.image' => 'O logo deve ser uma imagem.',
            'logo.mimes' => 'O logo deve ser um arquivo do tipo: jpg, jpeg, png, webp.',
            'logo.max' => 'O logo não pode ser maior que 2MB.',
            'cursos.array' => 'Os cursos devem ser um array.',
            'cursos.*.exists' => 'Um dos cursos selecionados é inválido.',
        ];
    }
}
