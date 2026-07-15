<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mcompany extends Model
{
    use HasFactory;

    protected $table = 'mcompany';

    protected $fillable = [
        'cname',
        'cemail',
    ];

    /**
     * Relasi ke user
     */
    // public function users()
    // {
    //     return $this->hasMany(muser::class, 'company_id', 'id');
    // }
}
