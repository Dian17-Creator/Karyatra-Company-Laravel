<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSchedule extends Model
{
    protected $table = 'tuserschedule';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    protected $fillable = [
        'nuserid', 'dwork', 'dstart', 'dend', 'dstart2', 'dend2', 'nidsched', 'cschedname'
    ];

    public function user()
    {
        return $this->belongsTo(muser::class, 'nuserid');
    }

    public function masterSchedule()
    {
        return $this->belongsTo(MasterSchedule::class, 'nidsched');
    }
}
