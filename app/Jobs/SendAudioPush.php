<?php

namespace App\Jobs;

use App\Models\AudioPublication;
use App\Models\MemberPushDevice;
use App\Services\FirebasePushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAudioPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public int $publicationId)
    {
    }

    public function handle(FirebasePushService $service): void
    {
        if (! config('services.firebase.push_enabled', false)) return;

        $publication = AudioPublication::with('audio')->find($this->publicationId);
        if (! $publication || ! $publication->audio || ! $publication->audio->is_published) {
            return;
        }
        $audio = $publication->audio;

        MemberPushDevice::query()
            ->whereHas('member', fn ($query) => $query->where('is_active', true)->where('role', 'member'))
            ->orderBy('id')
            ->chunkById(100, function ($devices) use ($service, $audio): void {
                foreach ($devices as $device) {
                    $result = $service->send(
                        $device->token,
                        'New '.ucfirst($audio->category).' available',
                        $audio->title,
                        ['audio_id' => (string) $audio->id],
                    );
                    if ($result === 'invalid') $device->delete();
                }
            });
    }
}
