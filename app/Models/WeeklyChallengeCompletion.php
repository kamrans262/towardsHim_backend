<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyChallengeCompletion extends Model
{
    protected $fillable = ['user_id', 'weekly_challenge_day_id'];

    public function day(): BelongsTo
    {
        return $this->belongsTo(WeeklyChallengeDay::class, 'weekly_challenge_day_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
