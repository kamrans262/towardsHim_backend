<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 100);
            $table->timestamps();
            $table->index(['created_at', 'id']);
        });

        Schema::create('community_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['community_post_id', 'created_at', 'id'], 'community_replies_thread_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_replies');
        Schema::dropIfExists('community_posts');
    }
};
