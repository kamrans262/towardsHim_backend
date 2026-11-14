<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MemberTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MemberAuthController extends Controller
{
    public function register(Request $request, MemberTokenService $tokens): JsonResponse
    {
        $payload = $request->all();
        $payload['email'] = strtolower(trim((string) ($payload['email'] ?? '')));
        $data = Validator::make($payload, [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ])->validate();

        return DB::transaction(function () use ($data, $tokens): JsonResponse {
            $user = User::create($data);
            return response()->json(['data' => $tokens->issue($user)], 201);
        });
    }

    public function login(Request $request, MemberTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower(trim($data['email'])))->first();
        if (! $user || ! $user->isMember() || ! $user->password ||
            ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials or inactive account.'], 422);
        }

        return response()->json(['data' => $tokens->issue($user)]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->publicProfile()]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $input = $request->all();
        if (array_key_exists('email', $input)) {
            $input['email'] = strtolower(trim((string) $input['email']));
        }

        $data = Validator::make($input, [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'bio' => ['sometimes', 'nullable', 'string', 'max:300'],
        ])->validate();

        if (array_key_exists('email', $data) && $data['email'] !== $user->email) {
            $user->email_verified_at = null;
        }
        $user->fill($data)->save();

        return response()->json(['data' => $user->fresh()->publicProfile()]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('member_token')->delete();
        return response()->json(['message' => 'Signed out.']);
    }

    public function logoutEverywhere(Request $request): JsonResponse
    {
        $request->user()->accessTokens()->delete();
        return response()->json(['message' => 'Signed out on all devices.']);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate(['password' => ['required', 'string']]);
        if (! $user->password || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Password confirmation required.'], 422);
        }
        $user->delete();
        return response()->json(['message' => 'Account deleted.']);
    }
}
