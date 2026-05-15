<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class mdepartment extends Model
{
    protected $table = 'mdepartment';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    // Tambahkan ini
    protected $fillable = [
        'cname',
    ];

    public function users()
    {
        return $this->hasMany(muser::class, 'niddept', 'nid');
    }
}
