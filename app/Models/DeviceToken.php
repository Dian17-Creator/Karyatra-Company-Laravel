<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $table = 'device_tokens';

    protected $fillable = [
        'nuserid',
        'fcm_token',
        'last_used_at'
    ];
    public function user()
    {
        return $this->belongsTo(muser::class, 'nuserid', 'nid');
    }
}
