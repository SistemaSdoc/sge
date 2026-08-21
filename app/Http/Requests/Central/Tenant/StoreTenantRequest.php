<?php

namespace App\Http\Requests\Central\Tenant;

use App\Models\Central\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = Auth::guard('web')->user();

        return $user?->hasPermissionTo('tenants.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
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
            'email' => ['required', 'email'],
            'telefone' => ['nullable', 'string'],
            'provincia' => ['nullable', 'string'],
            'endereco' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,trial,pending,suspended,inactive,archived'],
            'user_nome' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'email'],
        ];
    }
}
