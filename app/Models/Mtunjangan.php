<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mtunjangan extends Model
{
    protected $table = 'mtunjangan';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'nid',
        'tanggal_berlaku',
        'jenis_gaji',
        'nominal_gaji',
        'tunjangan_makan',
        'tunjangan_jabatan',
        'tunjangan_transport',
        'tunjangan_luar_kota',
        'tunjangan_masa_kerja',
        'tunjangan_backup',
    ];

    protected $casts = [
        'tanggal_berlaku' => 'date',
        'nominal_gaji' => 'decimal:2',
        'tunjangan_makan' => 'decimal:2',
        'tunjangan_jabatan' => 'decimal:2',
        'tunjangan_transport' => 'decimal:2',
        'tunjangan_luar_kota' => 'decimal:2',
        'tunjangan_masa_kerja' => 'decimal:2',
        'tunjangan_backup' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(Muser::class, 'nid', 'nid');
    }
}
