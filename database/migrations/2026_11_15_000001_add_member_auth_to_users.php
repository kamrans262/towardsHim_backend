<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default('member')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('bio', 300)->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('password')->nullable()->change();
            $table->string('email')->nullable()->change();
        });

        Schema::create('member_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('social_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('subject', 255);
            $table->timestamps();
            $table->unique(['provider', 'subject']);
            $table->unique(['user_id', 'provider']);
        });

        Schema::create('password_reset_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('code_hash', 255);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
        Schema::dropIfExists('social_identities');
        Schema::dropIfExists('member_access_tokens');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'is_active', 'bio', 'avatar_path']);
            $table->string('password')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
