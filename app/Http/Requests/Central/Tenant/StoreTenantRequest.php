<?php

namespace App\Http\Requests\Central\Tenant;

use App\Models\Central\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTenantRequest extends FormRequest
{
    /**
     * Verifica se o utilizador tem permissão para criar tenants.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = Auth::guard('web')->user();

        return $user?->hasPermissionTo('tenants.create') ?? false;
    }

    /**
     * Regras de validação para criação de tenant.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'unique:domains,domain'],
            'nome' => ['required', 'string', 'max:255'],
            'sigla' => ['required', 'string', 'max:10'],
            'tipo' => ['required', 'string', 'in:colegio,instituto'],
            'user_nome' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'email'],
        ];
    }

    /**
     * Mensagens de erro personalizadas.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'domain.required' => 'O subdomínio é obrigatório.',
            'domain.string' => 'O subdomínio pode conter apenas letras, números e hífens.',
            'domain.unique' => 'Este subdomínio já está a ser utilizado.',

            'nome.required' => 'O nome da instituição é obrigatório.',
            'nome.string' => 'O nome da instituição pode conter apenas letras e números.',
            'nome.max' => 'O nome não pode ter mais de 255 caracteres.',

            'sigla.required' => 'A sigla é obrigatória.',
            'sigla.string' => 'A sigla pode conter apenas letras.',
            'sigla.max' => 'A sigla não pode ter mais de 10 caracteres.',

            'tipo.required' => 'O tipo de instituição é obrigatório.',
            'tipo.string' => 'O tipo de instituição é inválido.',
            'tipo.in' => 'Escolha entre "Colégio" ou "Instituto".',

            'user_nome.required' => 'O nome do administrador é obrigatório.',
            'user_nome.string' => 'O nome do administrador pode conter apenas letras e espaços.',
            'user_nome.max' => 'O nome do administrador não pode ter mais de 255 caracteres.',

            'user_email.required' => 'O email do administrador é obrigatório.',
            'user_email.email' => 'Introduza um email válido (ex: admin@escola.ao).',
        ];
    }
}
