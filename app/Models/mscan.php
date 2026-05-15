<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class mscan extends Model
{
    use HasFactory;

    protected $table = 'mscan';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    protected $fillable = [
        'nuserId',
        'ntokenId',
        'nkioskId',
        'dscanned',
        'fmanual',
        'creason',
        'nlat',
        'nlng',
        'nadminid'
    ];

    protected $casts = [
        'dscanned' => 'datetime:Y-m-d H:i:s',
    ];

    // Relasi ke user (pegawai)
    public function user()
    {
        return $this->belongsTo(muser::class, 'nuserId');
    }

    // Relasi ke token lokasi
    public function token()
    {
        return $this->belongsTo(mtoken::class, 'ntokenId');
    }

    // Relasi ke admin yang menyetujui
    public function admin()
    {
        return $this->belongsTo(muser::class, 'nadminid', 'nid');
    }
}
