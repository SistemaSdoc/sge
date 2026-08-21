<?php

namespace App\Http\Requests\Central\Tenant;

use App\Models\Central\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = Auth::guard('web')->user();

        return $user?->hasPermissionTo('tenants.update') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id;

        return [
            'domain' => ['nullable', 'string', "unique:domains,domain,{$tenantId},tenant_id"],
            'nome' => ['nullable', 'string', 'max:255'],
            'sigla' => ['nullable', 'string', 'max:10'],
            'tipo' => ['nullable', 'string', 'in:colegio,instituicao,instituto,universidade'],
            'email' => ['nullable', 'email'],
            'telefone' => ['nullable', 'string'],
            'provincia' => ['nullable', 'string'],
            'endereco' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'descricao' => ['nullable', 'string'],
        ];
    }
}
