<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordCodeController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = strtolower(trim($data['email']));
        $user = User::where('email', $email)->where('role', 'member')->where('is_active', true)->first();

        if ($user && $user->password) {
            $code = (string) random_int(100000, 999999);
            DB::table('password_reset_codes')->updateOrInsert(
                ['email' => $email],
                [
                    'code_hash' => Hash::make($code),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(10),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
            Mail::raw("Your Toward HIM verification code is: $code. It expires in 10 minutes.", function ($message) use ($email): void {
                $message->to($email)->subject('Toward HIM verification code');
            });
        }

        return response()->json(['message' => 'If the account exists, a verification code has been sent.']);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = strtolower(trim($data['email']));
        $record = DB::table('password_reset_codes')->where('email', $email)->first();

        if (! $record || now()->greaterThan($record->expires_at) || $record->attempts >= 5 || strlen($record->code_hash) === 64 ||
            ! Hash::check($data['code'], $record->code_hash)) {
            if ($record) {
                DB::table('password_reset_codes')->where('email', $email)->increment('attempts');
            }
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        // One-time reset ticket. Never expose the OTP hash to the client.
        $ticket = bin2hex(random_bytes(32));
        DB::table('password_reset_codes')->where('email', $email)->update([
            'code_hash' => hash('sha256', $ticket),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);
        return response()->json(['data' => ['reset_ticket' => $ticket, 'expires_in_seconds' => 600]]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'reset_ticket' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $email = strtolower(trim($data['email']));
        $reset = DB::table('password_reset_codes')->where('email', $email)->first();
        if (! $reset || now()->greaterThan($reset->expires_at) ||
            ! hash_equals($reset->code_hash, hash('sha256', $data['reset_ticket']))) {
            return response()->json(['message' => 'Invalid or expired reset ticket.'], 422);
        }

        DB::transaction(function () use ($email, $data): void {
            $user = User::where('email', $email)->where('role', 'member')->lockForUpdate()->firstOrFail();
            $user->update(['password' => $data['password']]);
            $user->accessTokens()->delete();
            DB::table('password_reset_codes')->where('email', $email)->delete();
        });

        return response()->json(['message' => 'Password updated. Please sign in again.']);
    }
}
