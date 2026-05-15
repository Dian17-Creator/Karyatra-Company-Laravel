<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class mrequest extends Model
{
    use HasFactory;

    protected $table = 'mrequest';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    protected $fillable = [
        'nuserid',       // ✅ bukan nuserId
        'drequest',
        'nlat',
        'nlng',
        'cplacename',    // ✅ tambahkan ini
        'creason',
        'cphoto_path',
        'cstatus',
        'dcreated',
        'nadminid',
        'dupdated',
        'nsuperid',
        'csuperstat',
        'dsupuser',
        'nhrdid',
        'chrdstat',
        'duphrd',
    ];

    protected $casts = [
        'drequest' => 'datetime:Y-m-d H:i:s',
        'dcreated' => 'datetime:Y-m-d H:i:s',
        'dupdated' => 'datetime:Y-m-d H:i:s',
        'dsupuser' => 'datetime:Y-m-d H:i:s',
        'duphrd' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Relasi ke tabel user (pegawai yang mengajukan request)
     */
    public function user()
    {
        return $this->belongsTo(muser::class, 'nuserid');
    }

    /**
     * Relasi ke supervisor yang menyetujui
     */
    public function supervisor()
    {
        return $this->belongsTo(muser::class, 'nsuperid');
    }

    /**
     * Relasi ke HRD yang menyetujui
     */
    public function hrd()
    {
        return $this->belongsTo(muser::class, 'nhrdid');
    }
}
