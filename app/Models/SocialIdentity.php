<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialIdentity extends Model
{
    protected $fillable = ['user_id', 'provider', 'subject'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
