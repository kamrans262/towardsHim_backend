<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class LoginOtpService
{
    public const EXPIRES_IN_SECONDS = 600;
    private const MAX_ATTEMPTS = 5;
    private const MAX_SENDS = 3;
    private const RESEND_AFTER_SECONDS = 60;

    public function begin(User $user): array
    {
        $this->ensureMailer();
        $challenge = bin2hex(random_bytes(32));
        $code = (string) random_int(100000, 999999);
        $now = now();

        DB::transaction(function () use ($user, $challenge, $code, $now): void {
            // A new password login supersedes previous unverified attempts.
            DB::table('login_otp_challenges')->where('user_id', $user->id)->delete();

            DB::table('login_otp_challenges')->insert([
                'user_id' => $user->id,
                'challenge_hash' => hash('sha256', $challenge),
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'send_count' => 1,
                'last_sent_at' => $now,
                'expires_at' => $now->copy()->addSeconds(self::EXPIRES_IN_SECONDS),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->mailCode($user->email, $code);
        });

        return [
            'challenge' => $challenge,
            'email' => $user->email,
            'expires_in_seconds' => self::EXPIRES_IN_SECONDS,
            'resend_after_seconds' => self::RESEND_AFTER_SECONDS,
        ];
    }

    public function resend(string $challenge): array
    {
        $this->ensureMailer();

        return DB::transaction(function () use ($challenge): array {
            $record = $this->lockedChallenge($challenge);
            if (! $record) {
                throw ValidationException::withMessages(['code' => 'Verification has expired. Please log in again.']);
            }
            if ($record->send_count >= self::MAX_SENDS) {
                throw ValidationException::withMessages(['code' => 'Resend limit reached. Please log in again.']);
            }
            if (now()->diffInSeconds($record->last_sent_at, false) > -self::RESEND_AFTER_SECONDS) {
                throw ValidationException::withMessages(['code' => 'Please wait before requesting another code.']);
            }

            $user = User::find($record->user_id);
            if (! $user || ! $user->isMember() || ! $user->password) {
                DB::table('login_otp_challenges')->where('id', $record->id)->delete();
                throw ValidationException::withMessages(['code' => 'Verification has expired. Please log in again.']);
            }

            $code = (string) random_int(100000, 999999);
            DB::table('login_otp_challenges')->where('id', $record->id)->update([
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'send_count' => $record->send_count + 1,
                'last_sent_at' => now(),
                'expires_at' => now()->addSeconds(self::EXPIRES_IN_SECONDS),
                'updated_at' => now(),
            ]);
            $this->mailCode($user->email, $code);

            return [
                'expires_in_seconds' => self::EXPIRES_IN_SECONDS,
                'resend_after_seconds' => self::RESEND_AFTER_SECONDS,
            ];
        });
    }

    public function verify(string $challenge, string $code, MemberTokenService $tokens): array
    {
        $result = DB::transaction(function () use ($challenge, $code, $tokens): array {
            $record = $this->lockedChallenge($challenge);
            if (! $record) {
                return ['error' => 'Invalid or expired code. Please log in again.'];
            }

            if (! Hash::check($code, $record->code_hash)) {
                // Return the error after commit so the failed attempt persists.
                DB::table('login_otp_challenges')->where('id', $record->id)->increment('attempts');
                return ['error' => 'Invalid verification code.'];
            }

            $user = User::find($record->user_id);
            if (! $user || ! $user->isMember() || ! $user->password) {
                return ['error' => 'Account unavailable. Please log in again.'];
            }
            DB::table('login_otp_challenges')->where('user_id', $user->id)->delete();

            return ['data' => $tokens->issue($user)];
        });

        if (isset($result['error'])) {
            throw ValidationException::withMessages(['code' => $result['error']]);
        }

        return $result['data'];
    }

    private function lockedChallenge(string $challenge): ?object
    {
        $record = DB::table('login_otp_challenges')
            ->where('challenge_hash', hash('sha256', $challenge))
            ->lockForUpdate()
            ->first();

        if (! $record || now()->greaterThan($record->expires_at)
            || $record->attempts >= self::MAX_ATTEMPTS) {
            return null;
        }

        return $record;
    }

    private function mailCode(string $email, string $code): void
    {
        Mail::raw(
            "Your Toward HIM login verification code is: {$code}.\n\n"
            ."It expires in 10 minutes. If you did not attempt to sign in, ignore this email.",
            function ($message) use ($email): void {
                $message->to($email)->subject('Toward HIM login verification');
            }
        );
    }

    private function ensureMailer(): void
    {
        // Logging OTPs is never an acceptable production email delivery setup.
        if (app()->environment('production')
            && in_array(config('mail.default'), ['log', 'array'], true)) {
            abort(503, 'Email verification delivery is not configured. Contact support.');
        }
    }
}
