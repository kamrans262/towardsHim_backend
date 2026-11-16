<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WeeklyChallenge extends Model
{
    protected $fillable = ['title', 'instructions', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function media(): MorphMany
    {
        return $this->morphMany(ContentMedia::class, 'mediable')
            ->orderBy('position')->orderBy('id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(WeeklyChallengeDay::class)->orderBy('day_number');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function withMemberProgress(int $userId): static
    {
        return $this->loadCount([
            'days as completed_days' => fn ($query) => $query
                ->whereHas('completions', fn ($sub) => $sub->where('user_id', $userId)),
        ]);
    }

    public function toMobileArray(int $currentId, bool $withDays = false): array
    {
        $completed = (int) ($this->completed_days ?? 0);
        $published = $this->published_at;

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'is_current' => $this->id === $currentId,
            'start_date' => $published?->toDateString(),
            'end_date' => $published?->copy()->addDays(6)->toDateString(),
            'completed_days' => $completed,
            'total_days' => 7,
            'media' => $this->media->map(fn (ContentMedia $block) => $block->toMobileArray())->values(),
        ];

        if ($withDays) {
            $data['days'] = $this->days->map(fn ($day) => [
                'day' => $day->day_number,
                'text' => $day->activity,
                'completed' => $day->completions->isNotEmpty(),
                'media' => $day->media->map(fn (ContentMedia $block) => $block->toMobileArray())->values(),
            ])->values();
        }

        return $data;
    }
}
