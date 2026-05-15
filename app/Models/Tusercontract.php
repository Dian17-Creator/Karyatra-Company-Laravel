<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Tusercontract extends Model
{
    use HasFactory;

    protected $table = 'tusercontract';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    protected $fillable = [
        'nuserid',      // relasi ke muser
        'dstart',       // tanggal mulai kontrak
        'dend',         // tanggal akhir kontrak
        'nterm',        // durasi (3, 6, 12 bulan)
        'cnotes',       // catatan
        'cstatus',      // active / terminated
        'ctermtype',    // probation, promotion, evaluation
    ];

    protected $casts = [
        'dstart' => 'date:Y-m-d',
        'dend'   => 'date:Y-m-d',
        'nterm'  => 'integer',
    ];

    /**
     * Relasi ke user (pegawai)
     */
    public function user()
    {
        return $this->belongsTo(muser::class, 'nuserid', 'nid');
    }

    /**
     * Hitung sisa hari kontrak
     */
    public function getRemainingDaysAttribute()
    {
        if (!$this->dend) {
            return 0;
        }

        $end = \Carbon\Carbon::parse($this->dend)->endOfDay();
        $now = \Carbon\Carbon::now();

        // Mengembalikan selisih hari penuh antara sekarang dan tanggal akhir
        return $now->diffInDays($end, false);
    }


    /**
     * Status kontrak dalam format label (misal: Aktif / Habis)
     */
    public function getContractStatusLabelAttribute()
    {
        if ($this->cstatus === 'terminated') {
            return 'Dihentikan';
        }

        return $this->getRemainingDaysAttribute() > 0
            ? 'Aktif'
            : 'Sudah Habis';
    }
}
