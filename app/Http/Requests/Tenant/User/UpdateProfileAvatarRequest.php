<?php

namespace App\Http\Requests\Tenant\User;

use App\Models\Tenant\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileAvatarRequest extends FormRequest
{
    /** Autoriza a alteração do avatar do próprio usuário. */
    public function authorize(): bool
    {
        /** @var User|null $authenticatedUser */
        $authenticatedUser = auth('tenant')->user();
        $user = $this->route('user');

        return $user instanceof User && $authenticatedUser?->is($user);
    }

    /** Define as regras do ficheiro de avatar. */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
