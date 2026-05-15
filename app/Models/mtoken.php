<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class mtoken extends Model
{
    use HasFactory;

    protected $table = 'mtoken';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    protected $fillable = [
        'nlat',
        'nlng',
    ];

    public function scans()
    {
        return $this->hasMany(mscan::class, 'ntokenId', 'nid');
    }
}
