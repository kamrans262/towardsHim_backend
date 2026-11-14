<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialIdentity;
use App\Models\User;
use App\Services\MemberTokenService;
use App\Services\SocialTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SocialAuthController extends Controller
{
    public function google(Request $request, SocialTokenVerifier $verifier, MemberTokenService $tokens): JsonResponse
    {
        $data = $request->validate(['identity_token' => ['required', 'string', 'max:8192']]);
        try {
            return $this->authenticate('google', $verifier->google($data['identity_token']), $tokens);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], $this->status($exception));
        }
    }

    public function apple(Request $request, SocialTokenVerifier $verifier, MemberTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'identity_token' => ['required', 'string', 'max:8192'],
            'nonce' => ['nullable', 'string', 'max:256'],
        ]);
        try {
            return $this->authenticate('apple', $verifier->apple($data['identity_token'], $data['nonce'] ?? null), $tokens);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], $this->status($exception));
        }
    }

    private function authenticate(string $provider, array $identity, MemberTokenService $tokens): JsonResponse
    {
        return DB::transaction(function () use ($provider, $identity, $tokens): JsonResponse {
            $social = SocialIdentity::with('user')
                ->where('provider', $provider)->where('subject', $identity['subject'])
                ->lockForUpdate()->first();

            if ($social) {
                if (! $social->user->isMember()) {
                    return response()->json(['message' => 'Account is inactive.'], 403);
                }
                return response()->json(['data' => $tokens->issue($social->user)]);
            }

            // Never silently merge an OAuth identity into an existing email account.
            if ($identity['email'] && User::where('email', $identity['email'])->exists()) {
                return response()->json(['message' => 'This email has an existing account. Sign in with its original method.'], 409);
            }

            $user = User::create([
                'name' => $identity['name'] ?: ucfirst($provider).' Member',
                'email' => $identity['email'],
                'password' => null,
            ]);
            if ($identity['email']) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            $user->socialIdentities()->create(['provider' => $provider, 'subject' => $identity['subject']]);
            return response()->json(['data' => $tokens->issue($user)], 201);
        });
    }

    private function status(RuntimeException $error): int
    {
        return in_array($error->getCode(), [401, 503], true) ? $error->getCode() : 401;
    }
}
