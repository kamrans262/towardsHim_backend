<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_media', function (Blueprint $table): void {
            $table->id();
            $table->morphs('mediable');
            $table->string('type', 12);
            $table->longText('body')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->index(['mediable_type', 'mediable_id', 'position'], 'content_media_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_media');
    }
};
