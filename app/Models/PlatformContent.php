<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformContent extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'welcome_heading', 'introduction_text', 'about_information'];

    public const DEFAULTS = [
        'welcome_heading' => 'Welcome to Toward HIM',
        'introduction_text' => 'Podcasts, Meditations, Prayers, Challenges and a community to help you grow closer to HIM',
        'about_information' => 'Moving toward HIM is not about becoming perfect or having all the answers; it is about turning to Him, even when your heart feels heavy and your faith feels small.',
    ];

    public static function publicContent(): array
    {
        $content = static::query()->find(1);

        return [
            'welcome_heading' => $content?->welcome_heading ?? self::DEFAULTS['welcome_heading'],
            'introduction_text' => $content?->introduction_text ?? self::DEFAULTS['introduction_text'],
            'about_information' => $content?->about_information ?? self::DEFAULTS['about_information'],
            'store_url' => config('services.toward_him.store_url'),
        ];
    }
}
