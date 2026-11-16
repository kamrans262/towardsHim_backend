<?php

namespace App\Listeners;

use App\Events\AudioPublished;
use App\Jobs\SendAudioPush;
use App\Models\AudioPublication;
use Illuminate\Support\Facades\DB;

class RecordAudioPublication
{
    public function handle(AudioPublished $event): void
    {
        $audio = $event->audio;
        if (! $audio->is_published || ! $audio->published_at) {
            return;
        }

        // An audio item can only produce a single notification, even if
        // the administrator edits, unpublishes, and republishes it.
        $created = DB::table('audio_publications')->insertOrIgnore([
            'audio_item_id' => $audio->id,
            'published_at' => $audio->published_at,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($created && config('services.firebase.push_enabled', false)) {
            $publication = AudioPublication::where('audio_item_id', $audio->id)->first();
            if ($publication) SendAudioPush::dispatch($publication->id);
        }
    }
}
