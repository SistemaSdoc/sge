<?php

namespace App\Http\Controllers\Central\Auth;

use App\Http\Controllers\Controller;
use App\Models\Central\Role;
use App\Models\Central\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): Response
    {
        $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'telefone' => ['nullable', 'string', 'max:20'],
            'bi' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'nome' => $request->nome,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telefone' => $request->telefone,
            'bi' => $request->bi,
            'instituicao_id' => null,
        ]);

        $role = Role::where('nome', 'Candidato')->first();

        if ($role) {
            $user->roles()->attach($role->id, ['id' => (string) Str::uuid7()]);
        }

        event(new Registered($user));

        Auth::login($user);

        return response()->noContent();
    }
}
