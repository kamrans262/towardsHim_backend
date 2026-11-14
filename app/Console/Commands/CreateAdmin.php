<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    protected $signature = 'toward:admin-create {email} {--name=Administrator}';
    protected $description = 'Create an administrator through the secure server console';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');
            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('Email already exists. No account was changed.');
            return self::FAILURE;
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

        $this->info('Administrator created: '.$email);
        return self::SUCCESS;
    }
}
