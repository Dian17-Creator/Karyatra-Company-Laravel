<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminDevice extends Model
{
    protected $table = 'admin_devices';

    protected $fillable = [
        'admin_id',
        'device_id',
        'approval_status',
        'is_active',
        'last_used_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime'
    ];

    // Relasi ke muser
    public function user()
    {
        return $this->belongsTo(muser::class, 'admin_id', 'nid');
    }
}
