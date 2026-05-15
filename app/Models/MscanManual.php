<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MscanManual extends Model
{
    use HasFactory;

    protected $table = 'mscan_manual';
    protected $primaryKey = 'nid';
    public $timestamps = false; // karena kolom waktu kita pakai manual (NOW())

    protected $fillable = [
        'nuserId',
        'dscanned',
        'status',
        'nlat',
        'nlng',
        'nadminid',
        'nhrdid',
        'creason',
        'cphoto_path',
        'cstatus',
        'dapproved',
        'csuperstat',
        'dupsuper',
        'chrdstat',
        'duphrd',
    ];

    // Default value kalau buat instance baru
    protected $attributes = [
        'cstatus' => 'pending',
        'csuperstat' => 'pending',
        'chrdstat' => 'pending',
    ];

    // Relasi ke tabel users (kalau ada)
    public function user()
    {
        return $this->belongsTo(User::class, 'nuserId');
    }

    // Relasi ke admin / supervisor / HRD (opsional)
    public function admin()
    {
        return $this->belongsTo(User::class, 'nadminid');
    }
}
