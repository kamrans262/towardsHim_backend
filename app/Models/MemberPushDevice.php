<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPushDevice extends Model
{
    protected $fillable = ['user_id', 'token', 'token_hash', 'platform'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
