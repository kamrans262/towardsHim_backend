<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_items', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 20)->index();
            $table->string('title', 180);
            $table->text('description');
            $table->string('audio_path')->nullable();
            $table->string('artwork_path')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['category', 'is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_items');
    }
};
