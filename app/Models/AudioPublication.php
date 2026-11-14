<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioPublication extends Model
{
    protected $fillable = ['audio_item_id', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function audio(): BelongsTo
    {
        return $this->belongsTo(AudioItem::class, 'audio_item_id');
    }
}
