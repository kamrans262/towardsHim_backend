<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyChallengeDay extends Model
{
    protected $fillable = ['weekly_challenge_id', 'day_number', 'activity'];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(WeeklyChallenge::class, 'weekly_challenge_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(WeeklyChallengeCompletion::class);
    }
}
