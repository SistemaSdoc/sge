<?php

namespace App\Http\Controllers;

use App\Http\Resources\Auth\AuthUserResource;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me()
    {
        $user = auth()->user()->load('roles.permissions');

        return new AuthUserResource($user);
    }

    public function logout()
    {
        auth()->logout();

        $cookie = cookie()->forget('token');

        return response()->json(['message' => 'Sessão terminada'])->withCookie($cookie);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        $ttlMinutes = auth()->factory()->getTTL();

        $cookie = cookie(
            'token',
            $token,
            $ttlMinutes,
            '/',
            null,
            false,   // secure — false em dev, true em prod
            true,    // httpOnly
            false,
            'Lax'    // sameSite — Lax em dev, None em prod
        );

        $user = auth()->user()->load('roles.permissions');

        return response()->json(['data' => new AuthUserResource($user)])->withCookie($cookie);
    }
}
