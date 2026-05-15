<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class timport_userschedule extends Model
{
    protected $table = 'timport_userschedule';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    protected $fillable = [
        'nuserid', 'cusername', 'dwork', 'dstart', 'dend', 'dstart2', 'dend2', 'nidsched', 'cschedname'
    ];

}
