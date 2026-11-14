<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_challenges', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->text('instructions');
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('weekly_challenge_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('weekly_challenge_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->text('activity');
            $table->timestamps();
            $table->unique(['weekly_challenge_id', 'day_number'], 'challenge_day_unique');
        });

        Schema::create('weekly_challenge_completions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weekly_challenge_day_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'weekly_challenge_day_id'], 'member_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_challenge_completions');
        Schema::dropIfExists('weekly_challenge_days');
        Schema::dropIfExists('weekly_challenges');
    }
};
