<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $credentials = ['email' => $email, 'password' => $password];

        if (!$token = Auth::attempt($credentials)) {
            throw new \Exception('Invalid credentials', 401);
        }

        $user = Auth::user();

        return [
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => Auth::factory()->getTTL() * 60,
            'mfa_required' => $user->mfa_enabled,
        ];
    }

    public function logout(): void
    {
        Auth::logout();
    }

    public function refresh(): array
    {
        $token = Auth::refresh();

        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => Auth::factory()->getTTL() * 60,
        ];
    }

    public function me(): User
    {
        return Auth::user();
    }
}