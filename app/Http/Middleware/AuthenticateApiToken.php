<?php

namespace App\Http\Middleware;

use App\Models\MemberAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken();
        if (! $raw || strlen($raw) !== 64 || ! ctype_xdigit($raw)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = MemberAccessToken::with('user')
            ->where('token_hash', hash('sha256', $raw))
            ->where('expires_at', '>', now())
            ->first();

        if (! $token || ! $token->user?->isMember()) {
            return response()->json(['message' => 'Unauthenticated or account inactive.'], 401);
        }

        Auth::setUser($token->user);
        $request->attributes->set('member_token', $token);
        $token->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}
