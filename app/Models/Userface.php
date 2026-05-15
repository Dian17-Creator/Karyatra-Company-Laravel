<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\muser;

class Userface extends Model
{
    protected $table = 'tuserfaces';
    protected $primaryKey = 'nid';
    public $incrementing = true;
    public $timestamps = false;
    protected $fillable = [
        'nuserid',
        'cfilename',
        'dcreated'
    ];
    public function user()
    {
        return $this->belongsTo(muser::class, 'nuserid', 'nid');
    }
}
