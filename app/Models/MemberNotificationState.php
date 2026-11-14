<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberNotificationState extends Model
{
    protected $fillable = ['user_id', 'audio_publication_id', 'read_at', 'deleted_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'deleted_at' => 'datetime'];
    }
}
