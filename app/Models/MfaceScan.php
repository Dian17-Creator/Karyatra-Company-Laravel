<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfaceScan extends Model
{
    protected $table = 'mface_scan';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    protected $fillable = [
        'nuserId',
        'dscanned',
        'nlat',
        'nlng',
        'cplacename',
        'cphoto_path'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'nuserId', 'id');
    }
}
