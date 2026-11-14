<?php

namespace App\Events;

use App\Models\AudioItem;
use Illuminate\Foundation\Events\Dispatchable;

class AudioPublished
{
    use Dispatchable;

    public function __construct(public AudioItem $audio)
    {
    }
}
