<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_contents', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('welcome_heading', 180);
            $table->text('introduction_text');
            $table->text('about_information');
            $table->timestamps();
        });

        Schema::create('audio_publications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_item_id')->unique()->constrained('audio_items')->cascadeOnDelete();
            $table->timestamp('published_at')->index();
            $table->timestamps();
        });

        Schema::create('member_notification_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audio_publication_id')->constrained('audio_publications')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'audio_publication_id'], 'member_publication_unique');
        });

        Schema::create('member_push_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512);
            $table->string('token_hash', 64)->unique();
            $table->string('platform', 16);
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_push_devices');
        Schema::dropIfExists('member_notification_states');
        Schema::dropIfExists('audio_publications');
        Schema::dropIfExists('platform_contents');
    }
};
