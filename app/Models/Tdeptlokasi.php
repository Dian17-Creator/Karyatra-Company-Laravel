<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tdeptlokasi extends Model
{
    use HasFactory;

    protected $table = 'tdeptlokasi';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    protected $fillable = [
        'ndeptid',
        'cssid',
        'nlat',
        'nlng',
        'nradius',
        'dcreated',
    ];

    protected $casts = [
        'ndeptid' => 'integer',
        'nlat' => 'double',
        'nlng' => 'double',
        'nradius' => 'double',
        'dcreated' => 'datetime',
    ];

    /**
     * Relationship to the Department model
     */
    public function department()
    {
        return $this->belongsTo(mdepartment::class, 'ndeptid', 'nid');
    }
}
