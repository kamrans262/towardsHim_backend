<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AudioItem extends Model
{
    public const CATEGORIES = ['podcast', 'meditation', 'prayer'];

    protected $fillable = [
        'category', 'title', 'description', 'audio_path', 'artwork_path',
        'duration_seconds', 'is_published', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('audio_path')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getAudioUrlAttribute(): ?string
    {
        return $this->audio_path ? url(Storage::disk('public')->url($this->audio_path)) : null;
    }

    public function getArtworkUrlAttribute(): ?string
    {
        return $this->artwork_path ? url(Storage::disk('public')->url($this->artwork_path)) : null;
    }

    public function toMobileArray(): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'title' => $this->title,
            'description' => $this->description,
            'audio_url' => $this->audio_url,
            'artwork_url' => $this->artwork_url,
            'duration_seconds' => $this->duration_seconds,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
