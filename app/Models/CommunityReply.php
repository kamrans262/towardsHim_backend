<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityReply extends Model
{
    protected $fillable = ['community_post_id', 'user_id', 'body'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function toMobileArray(): array
    {
        $author = $this->author;
        return [
            'id' => (string) $this->id,
            'post_id' => (string) $this->community_post_id,
            'author_id' => (string) $this->user_id,
            'author_name' => $author?->name ?? 'Former Member',
            'author_avatar_url' => $author?->avatar_path
                ? asset('storage/'.$author->avatar_path) : null,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
