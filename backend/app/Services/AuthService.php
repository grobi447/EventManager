<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function login(array $credentials): array
    {
        if (! $token = auth('api')->attempt($credentials)) {
            throw new \Exception('Invalid credentials', 401);
        }

        $user = auth('api')->user();

        if ($user->mfa_enabled) {
            auth('api')->logout();

            return [
                'mfa_required' => true,
                'email' => $user->email,
            ];
        }

        return [
            'mfa_required' => false,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $user,
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
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
        ];
    }

    public function me(): User
    {
        return Auth::user();
    }
}
