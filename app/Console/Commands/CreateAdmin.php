<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    protected $signature = 'toward:admin-create {email} {--name=Administrator} {--grant-existing : Securely grant admin access to an existing member}';
    protected $description = 'Create an administrator through the secure server console';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');
            return self::FAILURE;
        }
        $existing = User::where('email', $email)->first();
        if ($existing) {
            if (! $this->option('grant-existing')) {
                $this->error('Email already exists. Use --grant-existing to explicitly authorize promotion.');
                return self::FAILURE;
            }

            if (! $existing->is_active) {
                $this->error('Account is inactive. No account was changed.');
                return self::FAILURE;
            }

            if ($existing->role === 'admin') {
                $this->info('Administrator already exists. Member access is also enabled.');
                return self::SUCCESS;
            }

            if ($existing->role !== 'member') {
                $this->error('Unsupported role. No account was changed.');
                return self::FAILURE;
            }

            if (! $existing->password) {
                $this->error('Existing social-only account has no password for web login. No account was changed.');
                return self::FAILURE;
            }

            if (! $this->confirm(
                'Grant administrator access to '.$email.'? The existing password and mobile app access will remain unchanged.',
                false
            )) {
                $this->warn('No account was changed.');
                return self::FAILURE;
            }

            $existing->forceFill(['role' => 'admin'])->save();
            $this->info('Administrator + member access enabled: '.$email);
            return self::SUCCESS;
        }

        $password = $this->secret('Admin password (at least 12 characters)');
        $confirm = $this->secret('Confirm admin password');
        if (! is_string($password) || strlen($password) < 12 || $password !== $confirm) {
            $this->error('Password too short or confirmation does not match.');
            return self::FAILURE;
        }

        $admin = new User();
        $admin->forceFill([
            'name' => (string) $this->option('name'),
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'role' => 'admin',
            'is_active' => true,
        ])->save();

        $this->info('Administrator created with web admin + mobile member access: '.$email);
        return self::SUCCESS;
    }
}
