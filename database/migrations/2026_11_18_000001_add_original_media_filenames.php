<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audio_items', function (Blueprint $table): void {
            $table->string('audio_original_name')->nullable();
            $table->string('video_original_name')->nullable();
            $table->string('artwork_original_name')->nullable();
        });

        Schema::table('content_media', function (Blueprint $table): void {
            $table->string('original_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('content_media', function (Blueprint $table): void {
            $table->dropColumn('original_name');
        });
        Schema::table('audio_items', function (Blueprint $table): void {
            $table->dropColumn([
                'audio_original_name',
                'video_original_name',
                'artwork_original_name',
            ]);
        });
    }
};
