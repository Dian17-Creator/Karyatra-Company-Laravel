<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mrekening extends Model
{
    use HasFactory;

    protected $table = 'mrekening';   // nama tabel

    protected $fillable = [
        'nomor_rekening',
        'bank',
        'atas_nama',
        'cabang',
    ];
    public function users()
    {
        return $this->hasMany(muser::class, 'rekening_id', 'id');
    }
}
