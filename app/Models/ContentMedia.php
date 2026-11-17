<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class ContentMedia extends Model
{
    protected $table = 'content_media';

    protected $fillable = ['type', 'body', 'file_path', 'original_name', 'position'];

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function toMobileArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'text' => $this->body,
            'url' => $this->file_path
                ? Storage::disk('public')->url($this->file_path)
                : null,
        ];
    }
}
