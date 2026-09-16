<?php

namespace App\Http\Requests\Tenant\User;

use App\Models\Tenant\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'telefone' => ['nullable', 'string', 'max:15'],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::in($this->allowedRoles())],
        ];
    }

    /** @return array<int, string> */
    private function allowedRoles(): array
    {
        /** @var User $actor */
        $actor = auth()->guard('tenant')->user();
        $user = $this->route('user');

        $query = Role::query()
            ->where('guard_name', 'tenant')
            ->whereNotIn('name', ['SuperAdmin']);

        if ($actor?->isSubdirector()) {
            $query->whereNotIn('name', ['Director', 'Subdirector']);
        }

        // inclui os roles actuais do user-alvo para não falhar validação
        $currentRoleNames = $user->roles->pluck('name')->all();

        return $query->pluck('name')
            ->merge($currentRoleNames)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Insira o nome do usuário.',
            'nome.string' => 'O nome do usuário deve ser um texto válido.',
            'nome.max' => 'O nome do usuário não pode ultrapassar 255 caracteres.',
            'email.required' => 'Insira o e-mail do usuário.',
            'email.email' => 'Insira um e-mail válido.',
            'email.max' => 'O e-mail não pode ultrapassar 255 caracteres.',
            'email.unique' => 'Este e-mail já está associado a outro usuário.',
            'telefone.string' => 'O telefone deve ser um texto válido.',
            'telefone.max' => 'O telefone não pode ultrapassar 15 caracteres.',
            'password.string' => 'A palavra-passe deve ser um texto válido.',
            'password.min' => 'A palavra-passe deve ter pelo menos 8 caracteres.',
            'roles.array' => 'A lista de roles deve ser válida.',
            'roles.*.exists' => 'Uma das roles selecionadas não existe.',
        ];
    }
}
