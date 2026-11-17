<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audio_items', function (Blueprint $table): void {
            $table->string('video_path')->nullable()->after('audio_path');
        });
    }

    public function down(): void
    {
        Schema::table('audio_items', function (Blueprint $table): void {
            $table->dropColumn('video_path');
        });
    }
};
