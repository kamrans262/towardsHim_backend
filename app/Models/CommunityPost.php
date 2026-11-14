<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPost extends Model
{
    protected $fillable = ['user_id', 'body'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CommunityReply::class);
    }

    public function toMobileArray(): array
    {
        $author = $this->author;
        return [
            'id' => (string) $this->id,
            'author_id' => (string) $this->user_id,
            'author_name' => $author?->name ?? 'Former Member',
            'author_avatar_url' => $author?->avatar_path
                ? asset('storage/'.$author->avatar_path) : null,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
            'reply_count' => (int) ($this->replies_count ?? $this->replies()->count()),
        ];
    }
}
