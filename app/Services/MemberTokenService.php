<?php

namespace App\Services;

use App\Models\User;

class MemberTokenService
{
    public function issue(User $user): array
    {
        $plain = bin2hex(random_bytes(32));
        $expires = now()->addDays(30);
        $user->accessTokens()->create([
            'token_hash' => hash('sha256', $plain),
            'expires_at' => $expires,
        ]);

        return [
            'token' => $plain,
            'token_type' => 'Bearer',
            'expires_at' => $expires->toIso8601String(),
            'user' => $user->publicProfile(),
        ];
    }
}
